<?php

namespace App\Common;

use Illuminate\Support\Carbon;

class Schedule
{
    public static function isOpen(string $phase): bool
    {
        $start = config('music.status.' . $phase . '.start');
        $end = config('music.status.' . $phase . '.end');
        if (empty($start) || empty($end)) {
            return false;
        }
        $now = Carbon::now();
        return $now->gte(Carbon::parse($start)) && $now->lte(Carbon::parse($end));
    }
}
