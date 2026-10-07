<?php
return [
    'loginUrl' => 'http://ykt.fzyz.net/appserver/login',
    'loginPublicKey' => '04c6b381c8dd86c4a6da68bc22e146a228389dc993a971d0e7d0d689b85775287ed06366740730d743b260db5f2f53c45ea6999b3e7f4ace0052944fa1b7e2f395',
    'loginSignSalt' => 'D3CAED7529EC4CD5D52EA7D31E26178A',
    'openUpload' => true,
    'openVote' => false,
    'openDownload' => false,
    'debugAuth' => env('MUSIC_DEBUG_AUTH', false),
    // 特殊测试账号,匹配学号/姓名/密码后跳过校园卡认证(仅用于测试)
    'testAccounts' => [
        [
            'cardNo' => '32000000000',
            'name' => '123',
            'password' => '000000'
        ]
    ],
    'playlist' => '2064024722',
    'status' => [
        //Format: ISO 8601 / RFC 2822 Date time
        //example: 1970-01-01 00:00
        'upload' => [
            'start' => '2026-10-01 00:00',
            'end' => '2026-10-30 00:00'
        ],
        'vote' => [
            'start' => '2026-10-30 00:00',
            'end' => '2026-11-18 00:00'
        ]
    ]
];
