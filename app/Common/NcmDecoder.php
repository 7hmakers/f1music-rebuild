<?php

namespace App\Common;

/**
 * 网易云音乐 NCM 文件解密器。
 *
 * NCM 结构: 魔数 "CTENFDAM" + 版本/间隔 + 加密的密钥块 + 加密的元数据 +
 *           间隔 + 封面图 + 经 RC4 加密的音频流(通常是 mp3 或 flac)。
 * 本类只负责解封装,不重新编码,输出原始音频交给后续流程处理。
 */
class NcmDecoder
{
    private const MAGIC = 'CTENFDAM';
    private const GAP_LEN = 2;

    // 密钥块解密用的 AES-128 key (ASCII "hzHRAmso5kInbaxW")
    private const CORE_KEY = 'hzHRAmso5kInbaxW';
    // 元数据解密用的 AES-128 key (ASCII "#14ljk_!\]&0U<'(")
    private const META_KEY = "#14ljk_!\\]&0U<'(";

    // 密钥块明文的前缀 "neteasecloudmusic"
    private const KEY_PREFIX_LEN = 17;
    // 元数据 XOR 与密钥块不同
    private const KEY_XOR = 0x64;
    private const META_XOR = 0x63;
    // 封面区偏移: 跳过 5 字节 -> 读 4 字节封面长度 -> +4
    private const COVER_GAP = 5;
    private const COVER_TRAILER = 13;

    private const CHUNK = 0x8000;

    public static function isNcm(string $path): bool
    {
        if (!is_file($path)) {
            return false;
        }
        $fh = @fopen($path, 'rb');
        if ($fh === false) {
            return false;
        }
        $magic = fread($fh, strlen(self::MAGIC));
        fclose($fh);
        return $magic === self::MAGIC;
    }

    /**
     * 将 NCM 解密为原始音频文件。
     * @return array{path:string,format:string,meta:?array}
     */
    public static function decode(string $path, string $outDir): array
    {
        $fh = @fopen($path, 'rb');
        if ($fh === false) {
            throw new \Exception('无法读取NCM文件');
        }
        try {
            if (fread($fh, strlen(self::MAGIC)) !== self::MAGIC) {
                throw new \Exception('不是有效的NCM文件');
            }
            fseek($fh, self::GAP_LEN, SEEK_CUR);

            $keyMaterial = self::readKey($fh);
            $keystream = self::buildKeyStream($keyMaterial);

            $meta = self::readMeta($fh);

            $audioStart = self::locateAudio($fh);
            $out = self::decryptAudio($fh, $audioStart, $keystream, $outDir);
        } finally {
            fclose($fh);
        }

        return ['path' => $out['path'], 'format' => $out['format'], 'meta' => $meta];
    }

    // 密钥块: XOR 0x64 -> AES-128-ECB -> 去掉 "neteasecloudmusic" 前缀
    private static function readKey($fh): string
    {
        $len = self::readUInt32($fh);
        if ($len <= 0) {
            throw new \Exception('NCM密钥块长度无效');
        }
        $raw = fread($fh, $len);
        if (strlen($raw) !== $len) {
            throw new \Exception('NCM密钥块不完整');
        }
        $plain = openssl_decrypt(self::xorByte($raw, self::KEY_XOR), 'aes-128-ecb', self::CORE_KEY, OPENSSL_RAW_DATA);
        if ($plain === false || strlen($plain) <= self::KEY_PREFIX_LEN) {
            throw new \Exception('NCM密钥解密失败');
        }
        return substr($plain, self::KEY_PREFIX_LEN);
    }

    // 元数据: XOR 0x63 -> 去22字节前缀 -> Base64 -> AES-128-ECB -> 去6字节 "music:" -> JSON
    private static function readMeta($fh): ?array
    {
        $len = self::readUInt32($fh);
        if ($len < 0) {
            throw new \Exception('NCM元数据长度无效');
        }
        if ($len === 0) {
            return null;
        }
        $raw = fread($fh, $len);
        if (strlen($raw) !== $len) {
            throw new \Exception('NCM元数据不完整');
        }
        $b64 = substr(self::xorByte($raw, self::META_XOR), 22);
        $decoded = base64_decode($b64, true);
        if ($decoded === false) {
            return null;
        }
        $plain = openssl_decrypt($decoded, 'aes-128-ecb', self::META_KEY, OPENSSL_RAW_DATA);
        if ($plain === false || strlen($plain) <= 6) {
            return null;
        }
        $meta = json_decode(substr($plain, 6), true);
        return is_array($meta) ? $meta : null;
    }

    // 音频起始位置 = 元数据结束位置 + 封面长度(位于 offset+5) + 13
    private static function locateAudio($fh): int
    {
        $offset = ftell($fh);
        $coverLen = self::readUInt32At($fh, $offset + self::COVER_GAP);
        if ($coverLen < 0) {
            throw new \Exception('NCM封面长度无效');
        }
        $start = $offset + $coverLen + self::COVER_TRAILER;
        if ($start <= $offset || $start >= self::fileSize($fh)) {
            throw new \Exception('无法定位NCM音频数据');
        }
        return $start;
    }

    private static function decryptAudio($fh, int $start, string $keystream, string $outDir): array
    {
        $tmp = rtrim($outDir, '/\\') . DIRECTORY_SEPARATOR . uniqid('ncm_') . '.tmp';
        $outFh = @fopen($tmp, 'wb');
        if ($outFh === false) {
            throw new \Exception('无法写入解密后的音频文件');
        }

        // 密钥流以 256 字节为周期,每块恰好覆盖整数个周期,可复用同一块
        $ksBlock = str_repeat($keystream, intdiv(self::CHUNK, 256));
        $first = '';

        fseek($fh, $start);
        while (!feof($fh)) {
            $chunk = fread($fh, self::CHUNK);
            if ($chunk === false || $chunk === '') {
                break;
            }
            $n = strlen($chunk);
            $mask = $n === self::CHUNK ? $ksBlock : substr($ksBlock, 0, $n);
            $plain = $chunk ^ $mask;
            if ($first === '') {
                $first = $plain;
            }
            fwrite($outFh, $plain);
        }
        fclose($outFh);

        clearstatcache(true, $tmp);
        if (!is_file($tmp) || filesize($tmp) === 0) {
            @unlink($tmp);
            throw new \Exception('NCM解密结果为空');
        }

        $format = strncmp($first, 'fLaC', 4) === 0 ? 'flac' : 'mp3';
        $out = substr($tmp, 0, -4) . '.' . $format;
        if (!@rename($tmp, $out)) {
            @unlink($tmp);
            throw new \Exception('无法保存解密后的音频文件');
        }
        return ['path' => $out, 'format' => $format];
    }

    // 标准 RC4 KSA + 预计算 PRGA 密钥流(把经典实现的 (i+1) 偏移烘焙进下标)
    private static function buildKeyStream(string $key): string
    {
        $box = range(0, 255);
        $keyLen = strlen($key);
        if ($keyLen === 0) {
            return str_repeat("\x00", 256);
        }
        $j = 0;
        for ($i = 0; $i < 256; $i++) {
            $j = ($box[$i] + $j + ord($key[$i % $keyLen])) & 0xFF;
            $swap = $box[$i];
            $box[$i] = $box[$j];
            $box[$j] = $swap;
        }

        $ks = '';
        for ($i = 0; $i < 256; $i++) {
            $idx = ($i + 1) & 0xFF;
            $si = $box[$idx];
            $sj = $box[($idx + $si) & 0xFF];
            $ks .= chr($box[($si + $sj) & 0xFF]);
        }
        return $ks;
    }

    private static function xorByte(string $data, int $byte): string
    {
        return $data ^ str_repeat(chr($byte), strlen($data));
    }

    private static function readUInt32($fh): int
    {
        $b = fread($fh, 4);
        if (strlen($b) !== 4) {
            return -1;
        }
        return unpack('V', $b)[1];
    }

    private static function readUInt32At($fh, int $pos): int
    {
        $cur = ftell($fh);
        fseek($fh, $pos);
        $v = self::readUInt32($fh);
        fseek($fh, $cur);
        return $v;
    }

    private static function fileSize($fh): int
    {
        $stat = fstat($fh);
        return $stat ? (int) $stat['size'] : 0;
    }
}
