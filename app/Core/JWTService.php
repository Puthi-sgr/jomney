<?php

namespace App\Core;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Exception;

class JWTService
{
   private static string $secret;
   private static int $ttl;
   private static string $algo = 'HS256';

   public static function init(): void{
    self::$secret = $_ENV["JWT_SECRET"];
    self::$ttl = (int) ($_ENV['JWT_TTL'] ?? 3600);
   }

   //Typically happens in login
   public static function generateToken(int|string $userId): string{
        $now = time();
        $exp = $now + self::$ttl;

        $payload = [ 
            "iss" => $_ENV["APP_URL" ?? 'http://localhost'],
            "iat" => $now,
            "exp" => $exp,
            "sub" => $userId
        ]; 

        $encoded = JWT::encode($payload, self::$secret, self:: $algo );
        return  $encoded;
    }

    public static function validateToken(string $token):object{
        $decode = JWT::decode($token, new Key(self::$secret, self::$algo));
        
        return $decode;
    }
}

JWTService::init();

