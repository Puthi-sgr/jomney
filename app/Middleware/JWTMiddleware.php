<?php


namespace App\Middleware;

use App\Core\JWTService;
use App\Core\Response;
use Exception;

class JWTMiddleware{
    public static function check():void {
        //Get authorization
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? 
                  $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? 
                  getallheaders()['Authorization'] ?? 
                  getallheaders()['authorization'] ?? '';



        if (!preg_match('/Bearer\s(\S+)/', $authHeader, $matches)){
            Response::error('Token not provided', [], 401);
            return;
        }

        $token = $matches[1];

        // Debug: Log the token we extracted
    error_log("Extracted token: " . $token);
    error_log("Token length: " . strlen($token));

        try{
            //2. Validate the token
            $payload = JWTService::validateToken($token);

            //3.Attach user ID
            $_SERVER['user_id'] = $payload->sub;
           
        }catch(Exception $e){
            Response::error('Invalid token', ["message" => $e->getMessage(), "stackTrace" => $e->getTrace()], 401);
        }
    }

}