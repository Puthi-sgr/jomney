<?php

namespace App\Middleware;

class AuthMiddleware{
    public static function check(){
      
    
        $sessionId = session_id();
        echo "$sessionId";
        //checks if there is that use id's session
        if(!isset($_SESSION['user_id'])){
            http_response_code(401);
            echo "Unauthorized access. (Reaching the middleware)";
            exit;
        }
    }
}