<?php

namespace App\Http\Controllers;

use App\Common\NcmDecoder;
use App\Common\Schedule;
use App\Models\File;
use App\Models\Song;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use getID3;
use Metowolf\Meting;
use getid3_writetags;
use Symfony\Component\Process\Process;

class UploadController extends Controller
{
    // 允许上传的音频格式(以内容识别的MIME为准),非mp3会被转码为mp3
    private const audioMimetypes = 'audio/mpeg,audio/mp3,audio/mp4,video/mp4,audio/x-m4a,'
        . 'audio/aac,audio/x-aac,audio/wav,audio/x-wav,audio/wave,audio/vnd.wave,'
        . 'audio/flac,audio/x-flac,audio/ogg,application/ogg,audio/opus,audio/vorbis,'
        . 'audio/x-ms-wma,audio/x-ms-wax,video/x-ms-asf,application/vnd.ms-asf,'
        . 'audio/aiff,audio/x-aiff,audio/basic,application/octet-stream';

    // 转码目标比特率(不低于原有的128kbps门槛)
    private const transcodeBitrate = '192k';

    // 非mp3源文件的最低比特率(阈值放宽以避免AAC等格式的误判,无损格式不受影响)
    private const minSourceBitrate = 96000;

    private const messages = [
        'time.required' => '请选择时段',
        'time.in' => '参数错误,请重新选择时段',
        'name.required' => '请填写曲名',
        'name.max' => '歌曲名称不得超过50个字符',
        'origin.max' => '歌曲来源不得超过50个字符',
        'id.required_without' => '参数错误,请重新搜索音乐',
        'file.required_without' => '请上传一个文件',
        'file.file' => '上传的文件无效,请重新上传',
        'file.max' => '禁止上传超过20MB的文件',
        'file.min' => '为保证音频质量,请上传一个至少1MB的文件',
        'file.mimetypes' => '不支持的文件格式,请上传常见的音频文件(mp3/m4a/aac/wav/flac/ogg等)或网易云NCM文件'
    ];

    private const errorMsg = [
        'stop_upload' => '上传已关闭',
        'max_upload_num' => '上传曲目数已达到限定数目,感谢您的支持',
        'time_too_long' => '歌曲时长超过6分钟,请选择短一些的曲目',
        'time_too_long_3' => '午出门铃声时长不得大于5分钟',
        'time_too_short' => '歌曲时长不足2分半,请选择长一些的曲目',
        'already_exists' => '曲目在该时段已经有人推荐',
        'mp1_or_mp2' => '文件为mp1或mp2格式,请上传mp3格式的文件',
        'bitrate_too_low' => '音频比特率低于128kbps,请上传音质较高的文件'
    ];

    public function upload(Request $request)
    {
        if (!config('music.openUpload') || !Schedule::isOpen('upload')) {
            return $this->error(self::errorMsg['stop_upload'], 2);
        } elseif (Song::withTrashed()->where('user_id', Auth::id())->count() >= 12) {
            return $this->error(self::errorMsg['max_upload_num']);
        }
        Log::info('Requests: ' . var_export($request->all(), true));
        Validator::make($request->all(), [
            'time' => [
                'required',
                Rule::in(['1', '2', '3', '4', '5', '6'])
            ],
            'name' => 'required | string | max: 100',
            'origin' => 'nullable | string | max: 200',
            'id' => ['required_without:file', 'numeric', 'integer', 'min:0'],
            'file' => ['required_without:id', 'file', 'mimetypes:' . self::audioMimetypes, 'min: 1024', 'max: 20480']
            //部分文件的mimetype会被识别为application/octet-stream,此处放行,后面交由getID3与ffmpeg验证
            //非mp3格式会在getFileFromRequest中统一转码为mp3
        ], self::messages)->validate();

        try {
            // $uFile => Unvalidated File
            $uFile = self::getFileFromRequest($request);
            // $vFile => Validated File
            $vFile = self::validateFile($uFile);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }

        if (empty($vFile->error)) {
            $vFile = self::storeFile($vFile);
            self::insertSong($vFile);
        } else {
            Storage::disk('tmp')->delete($vFile->name);
            return $this->error($vFile->error);
        }
        return $this->success();
    }

    public function uploads()
    {
        if (!config('music.openUpload') || !Schedule::isOpen('upload')) {
            return $this->error(self::errorMsg['stop_upload'], 2);
        }
        $songs = Song::withTrashed()->where('user_id', Auth::id())->get();
        $songs = $songs->map(function ($song) {
            return $song->only(['id', 'playtime', 'name', 'origin']);
        });
        return $this->success('songs', $songs);
    }

    public function edit(Request $request)
    {
        if (!config('music.openUpload') || !Schedule::isOpen('upload')) {
            return $this->error(self::errorMsg['stop_upload'], 2);
        }
        $validator = Validator::make($request->all(), [
            'id' => ['required', 'numeric', 'integer', 'min:0'],
            'time' => [
                'required',
                Rule::in(['1', '2', '3', '4', '5', '6'])
            ],
            'name' => 'required | string | max: 100',
            'origin' => 'nullable | string | max: 200'
        ], self::messages);
        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 2);
        }

        $song = Song::where('id', $request->input('id'))->where('user_id', Auth::id())->first();
        if (empty($song)) {
            return $this->error('曲目不存在或无权修改', 2);
        }
        $time = $request->input('time');
        if ($time != $song->playtime) {
            $exists = Song::withTrashed()->ofTime($time)->where('file_id', $song->file_id)
                ->where('id', '!=', $song->id)->exists();
            if ($exists) {
                return $this->error('目标时段已存在相同曲目', 2);
            }
            if ($time == '3' && $song->file->duration > 5 * 60) {
                return $this->error(self::errorMsg['time_too_long_3'], 2);
            }
        }
        $song->name = $request->input('name');
        $song->origin = $request->input('origin');
        $song->playtime = $time;
        $song->save();
        Log::info('Song ' . $song->id . ' edited by ' . Auth::id());
        return $this->success();
    }

    public function withdraw(Request $request)
    {
        if (!config('music.openUpload') || !Schedule::isOpen('upload')) {
            return $this->error(self::errorMsg['stop_upload'], 2);
        }
        $validator = Validator::make($request->all(), [
            'id' => ['required', 'numeric', 'integer', 'min:0']
        ], ['id.required' => '参数错误,请刷新页面']);
        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 2);
        }

        $song = Song::withTrashed()->where('id', $request->input('id'))->where('user_id', Auth::id())->first();
        if (empty($song)) {
            return $this->error('曲目不存在或无权操作', 2);
        }
        // SongDeleted 事件会在该文件无其它引用时顺带清理文件
        $song->forceDelete();
        Log::info('Song ' . $request->input('id') . ' withdrawn by ' . Auth::id());
        return $this->success();
    }

    private static function getFileFromRequest(Request $request)
    {
        $tmpDir = storage_path('app/tmp/');
        $file = new UnvalidatedFile();
        $file->time = $request->input('time');
        $file->songName = $request->input('name');
        $file->songOrigin = $request->input('origin');

        if ($request->hasFile('file')) { //Manual Upload
            $reqFile = $request->file('file');
            $file->name = $reqFile->getClientOriginalName();
            $file->url = $tmpDir . $file->name;
            $reqFile->move($tmpDir, $file->name); //先存储到临时目录以便验证
            if (NcmDecoder::isNcm($file->url)) {
                //NCM为加密容器,先解密还原出内部的mp3/flac,再交给后续流程统一处理
                try {
                    $decoded = NcmDecoder::decode($file->url, $tmpDir);
                } catch (\Exception $e) {
                    @unlink($file->url);
                    throw $e;
                }
                @unlink($file->url);
                $file->name = basename($decoded['path']);
                $file->url = $decoded['path'];
            }
        } else { //Cloud Music Upload
            $url = self::getMp3($request->input('id'));
            $file->cloudId = $request->input('id');
            if (empty($url)) {
                throw new \Exception('暂不支持无版权或付费歌曲,请手动上传');
            }
            $pathInfo = pathinfo($url);
            $file->name = $pathInfo['basename'];
            Storage::disk('tmp')->put($file->name, file_get_contents($url));
            $file->url = $tmpDir . $file->name;
        }
        // 统一转换为mp3(非mp3格式在此转码),再按最终文件内容计算md5用于去重与存储
        self::ensureMp3($file);
        $file->md5 = md5_file($file->url);
        return $file;
    }

    // 确保临时文件为mp3,否则用ffmpeg转码;失败时清理临时文件并抛出异常
    private static function ensureMp3($file)
    {
        $info = self::probeAudio($file->url);
        $format = $info['format'] ?? null;

        if ($format === 'mp3') { //已是mp3,无需处理
            return;
        }
        try {
            //非mp3文件保留音质门槛(无损格式比特率足够高,通常不会被拦截)
            if ($info !== null && !empty($info['bitrate']) && $info['bitrate'] < self::minSourceBitrate) {
                throw new \Exception(self::errorMsg['bitrate_too_low']);
            }
            self::transcodeToMp3($file);
        } catch (\Exception $e) {
            @unlink($file->url);
            throw $e;
        }
    }

    private static function transcodeToMp3($file)
    {
        $dir = dirname($file->url);
        $output = $dir . DIRECTORY_SEPARATOR . uniqid('mp3_') . '.mp3';

        $process = new Process([
            'ffmpeg', '-hide_banner', '-loglevel', 'error', '-y',
            '-i', $file->url,
            '-vn', '-map_metadata', '-1',
            '-c:a', 'libmp3lame', '-b:a', self::transcodeBitrate,
            $output,
        ]);
        $process->setTimeout(600);
        $process->run();

        if (!$process->isSuccessful() || !is_file($output) || filesize($output) === 0) {
            @unlink($output);
            Log::warning('Transcode failed: ' . $process->getErrorOutput());
            throw new \Exception('文件不是有效的音频,或格式不受支持');
        }
        @unlink($file->url); //删除原始临时文件
        $file->name = basename($output);
        $file->url = $output;
    }

    private static function validateFile($file)
    {
        $file->error = null;
        $file->storageName = $file->md5 . '.mp3';
        $eFile = File::where('md5', $file->md5); //eFile->Existing File

        if ($eFile->exists()) {
            $file->id = $eFile->first()->id; //文件已上传过,获取ID
            $file->duration = $eFile->first()->duration;

            if (Song::withTrashed()->ofTime($file->time)->where('file_id', $file->id)->exists()) {
                $file->error = self::errorMsg['already_exists']; //音乐在该时段已经有人推荐
            } else {
                //文件已经上传该时段但还未有人推荐,则只需验证时长
                if ($file->time == '3') {
                    $file = self::checkDuration($file);
                }
                if (empty($file->error)) {
                    Storage::disk('tmp')->delete($file->name);
                } else {
                    return $file;
                }
            }
        } else { //文件未上传过,则进行验证
            $info = self::getInfo($file);
            $format = $info['format'];
            $duration = $info['duration'];
            $file->duration = $duration;

            if (in_array($format, ['mp1', 'mp2'])) {
                $file->error = self::errorMsg['mp1_or_mp2'];
            } elseif ($format != 'mp3') {
                $file->error = self::messages['file.mimetypes'];
            } elseif ($info['bitrate'] < 128000) {
                $file->error = self::errorMsg['bitrate_too_low'];
            }
            $file = self::checkDuration($file);
        }
        return $file;
    }

    private static function insertSong($file)
    {
        $song = Song::create([
            'playtime' => $file->time,
            'name' => $file->songName,
            'origin' => $file->songOrigin,
            'user_id' => Auth::id(),
            'file_id' => $file->id
        ]);
        $song->save();
    }

    private static function storeFile($vFile)
    {
        if (empty($vFile->id)) { //未上传过则存储
            self::removeTags($vFile);
            Storage::move('tmp/' . $vFile->name, 'uploads/' . $vFile->storageName);
            $file = File::create([
                'md5' => $vFile->md5,
                'duration' => $vFile->duration,
                'cloud_id' => $vFile->cloudId,
                'user_id' => Auth::id()
            ]);
            $file->save();
            $vFile->id = $file->id;
        }
        return $vFile;
    }

    private static function getInfo($file)
    {
        $mp3Info = self::analyze($file->url);
        if (!empty($mp3Info['error'])) {
            Log::info("Error: ", $mp3Info['error']);
            throw new \Exception('文件已损坏或不是有效的音频');
        }
        $info = [
            'format' => $mp3Info['fileformat'],
            'bitrate' => $mp3Info['bitrate'],
            'duration' => intval($mp3Info['playtime_seconds'])
        ];
        Log::info('Info:', $info);
        return $info;
    }

    // 探测音频信息,无法识别时返回null
    private static function probeAudio($path)
    {
        $info = self::analyze($path);
        if (!empty($info['error']) || empty($info['fileformat'])) {
            return null;
        }
        return [
            'format' => $info['fileformat'],
            'bitrate' => $info['bitrate'] ?? null,
            'duration' => intval($info['playtime_seconds'] ?? 0)
        ];
    }

    private static function analyze($path)
    {
        $getID3 = new getID3;
        $getID3->setOption([
            'option_tag_id3v1' => false,
            'option_tag_id3v2' => false,
            'option_tag_lyrics3' => false,
            'option_tag_apetag' => false,
            'option_md5_data' => false,
            'option_sha1_data' => false,
            'option_max_2gb_check' => false
        ]);
        return $getID3->analyze($path);
    }

    private static function checkDuration($file)
    {
        if (!empty($file->error)) { //不覆盖已有错误信息
            return $file;
        }
        if ($file->duration < 2.5 * 60) {
            $file->error = self::errorMsg['time_too_short'];
            return $file;
        }
        if ($file->time == '3') {
            if ($file->duration > 5 * 60) {
                $file->error = self::errorMsg['time_too_long_3'];
            }
        } elseif ($file->duration > 6 * 60) {
            $file->error = self::errorMsg['time_too_long'];
        }
        return $file;
    }

    private static function removeTags($file)
    {
        $writer = new getid3_writetags;
        $writer->filename = $file->url;
        $writer->overwrite_tags = true;
        $writer->remove_other_tags = true;
        $writer->WriteTags();
    }

    private static function getMp3($id)
    {
        $api = new Meting('netease');
        $res = $api->format(false)->url($id, 128);
        $res = json_decode($res, true);
        $res = $res["data"][0];
        if ($res["freeTrialInfo"] == null) {
            $url = $res["url"];
        } else {
            $url = "";
        }
        return $url;
    }
}

class UnvalidatedFile
{
    public $id = null;
    public $name = null;
    public $time = null;
    public $songName = null;
    public $songOrigin = null;
    public $url = null;
    public $cloudId = null;
    public $md5 = null;
}