<?php

namespace App\Common;

use App\Common\AuthData;
use App\Common\AuthResult;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Rtgm\sm\RtSm2;
use Rtgm\sm\RtSm3;

class CampusAuth
{
    public static function login(AuthData $authData): AuthResult
    {
        if (config('music.debugAuth')) {
            return AuthResult::Success;
        }

        // 特殊测试账号,跳过校园卡认证
        foreach (config('music.testAccounts', []) as $account) {
            if (
                $authData->cardNo === ($account['cardNo'] ?? null) &&
                $authData->name === ($account['name'] ?? null) &&
                $authData->password === ($account['password'] ?? null)
            ) {
                return AuthResult::Success;
            }
        }

        $payload = [
            'userName' => $authData->name,
            'userPassword' => $authData->password,
            'cardNo' => $authData->cardNo,
            'cifNumber' => null,
        ];
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $sm2 = new RtSm2();
        $cipher = $sm2->doEncrypt($json, config('music.loginPublicKey'), C1C3C2);
        $body = base64_encode(hex2bin($cipher));
        $sign = strtoupper((new RtSm3())->digest($json . config('music.loginSignSalt')));

        try {
            $response = Http::timeout(5)
                ->withHeaders([
                    'Content-Type' => 'application/json;charset=utf-8',
                    'sign' => $sign,
                    'token' => '',
                ])
                ->withBody($body, 'application/json;charset=utf-8')
                ->post(config('music.loginUrl'));

            if ($response->failed()) {
                return AuthResult::ConnectionError;
            }
            return (($response->json('code') ?? null) === '200') ? AuthResult::Success : AuthResult::Failed;
        } catch (ConnectionException) {
            return AuthResult::ConnectionError;
        }
    }
}
