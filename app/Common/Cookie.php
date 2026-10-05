<?php

namespace App\Common;

use App\Models\User;
use Illuminate\Support\Facades\Cookie as CookieFacade;

class Cookie
{
    protected static $name = 'f1music_user';
    protected static $minutes = 60 * 24 * 60;

    public static function make(User $user)
    {
        $data = json_encode([
            'id' => $user->id,
            'name' => $user->name,
            'permission' => (int) ($user->permission ?? Permission::User->value)
        ]);
        return cookie(self::$name, base64_encode($data), self::$minutes, '/', httpOnly: false);
    }

    public static function forget()
    {
        CookieFacade::expire(self::$name);
    }
}
