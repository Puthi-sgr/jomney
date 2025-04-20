<?php

namespace App\Middleware;

class AuthMiddleware{
    public static function check(){
        session_start();
        if(!isset($_SESSION['user_id'])){
            http_response_code(401);
            echo "Unauthorized access.";
            exit;
        }
    }
}