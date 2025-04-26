<?php

namespace App\Middleware;
use App\Core\Response;

class AuthMiddleware{
    public static function check(){
        $sessionId = session_id();
        // echo "$sessionId";
        //checks if there is that use id's session
        if(!isset($_SESSION['user_id'])){
            Response::error("Unauthorized access. ", ["sessionId" => $sessionId], 401);
            exit;
        }
    }
}