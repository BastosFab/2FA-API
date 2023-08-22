<?php

namespace App\Services;

use App\Entity\Token;

class TokenService
{
    /**
     * @var Token $token
     */
    public $token = null;

    static function generateToken(): string
    {
        return bin2hex(random_bytes(10));
    }

}
