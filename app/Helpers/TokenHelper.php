<?php

namespace App\Helpers;

use Emarref\Jwt\Algorithm\Hs256;
use Emarref\Jwt\Jwt;
use Emarref\Jwt\Token;
use Emarref\Jwt\Claim;
use Emarref\Jwt\Encryption\Factory;
use Illuminate\Support\Facades\Config;

class TokenHelper {

    public static function generateToken($payLoad)
    {
        $serializedToken = '';
        
        //Payload Parameter should be an Associative Array with Key & Value.
        if (sizeof($payLoad) > 0) {
            $token = new Token();
            $token->addClaim(new Claim\IssuedAt(date('Y-m-d H:i:s')));

            foreach ($payLoad as $key => $value) {
                $token->addClaim(new Claim\PrivateClaim($key, $value));
            }

            $jwt = new Jwt();
            $secret = Config::get('constants.key');
            $algorithm  = new Hs256($secret);
            $encryption = Factory::create($algorithm);
            $serializedToken = $jwt->serialize($token, $encryption);
        }

        return $serializedToken;
    }
}

?>