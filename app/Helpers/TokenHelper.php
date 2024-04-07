<?php

namespace App\Helpers;

use Emarref\Jwt\Algorithm\Hs256;
use Emarref\Jwt\Exception\VerificationException;
use Emarref\Jwt\Jwt;
use Emarref\Jwt\Token;
use Emarref\Jwt\Claim;
use Emarref\Jwt\Encryption\Factory;
use Emarref\Jwt\Verification\Context;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

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

    public static function verifyToken($serializedToken)
    {
        try {
            $validToken = false;

            $jwt = new Jwt();
            $token = $jwt->deserialize($serializedToken);

            $secret = Config::get('constants.key');
            $algorithm  = new Hs256($secret);
            $encryption = Factory::create($algorithm);
            $context = new Context($encryption);

            try {
                $validToken = $jwt->verify($token, $context);
            } catch (VerificationException $e) {
                Log::info('In-valid Token');
                Log::info($e->getMessage());
            }
        } catch (\Exception $e) {
            Log::info('Error Decoding Token');
            Log::info($e->getMessage());
        }

        return $validToken;
    }

    public static function getTokenPayload($serializedToken)
    {
        try {
            $result = array();
            $jwt = new Jwt();
            $token = $jwt->deserialize($serializedToken);

            $header = $token->getHeader()->jsonSerialize();
            $playload = $token->getPayload()->jsonSerialize();
            $result = json_decode($playload, true);
        } catch (\Exception $e) {
            Log::info('Error Decoding Token');
            Log::info($e->getMessage());
        }

        return $result;
    }
}