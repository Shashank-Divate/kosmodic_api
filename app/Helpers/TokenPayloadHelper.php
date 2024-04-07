<?php

namespace App\Helpers;

use App\Helpers\TokenHelper;

class TokenPayloadHelper
{
    public static function getUserRole($token)
    {
        $payload = TokenHelper::getTokenPayload($token);
        
        $user_role = $payload['user_role'];

        return $user_role;
    }
}
