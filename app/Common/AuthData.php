<?php

namespace App\Common;

class AuthData
{
    public function __construct(
        public string $name,
        public string $cardNo,
        public string $password
    ) {
    }
}
