<?php

namespace App\Core;

use Firebase\JWT\JWT;
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

   public static function generateToken(int|string $userId): string{
        $now = time();
        $exp = $now + self::$ttl;

        $payload = [ 
            "iss" => $_ENV["APP_URL"] ?? 'http://localhost',
            "iat" => $now,
            "exp" => $exp,
            "sub" => $userId
        ]; 

        return JWT::encode($payload, self::$secret, self::$algo);
    }

    public static function validateToken(string $token): object{
        try {
            // Use v5 compatibility syntax that works with your setup
            $decoded = JWT::decode($token, self::$secret, [self::$algo]);
            
            return $decoded;
            
        } catch (Exception $e) {
            error_log("JWT validation error: " . $e->getMessage());
            throw new Exception("Invalid token: " . $e->getMessage());
        }
    }
}

JWTService::init();
