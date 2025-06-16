<?php

namespace App\core;

use Throwable;

class ErrorHandler {
    public static function handleException(Throwable $e){
        http_response_code(500);

        if($_ENV['APP_ENV'] === "development"){

            Response::error($e->getMessage(), 
            ["line"=> $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode(), 'stackTrace' => $e->getTrace()], 500);
        }else{ 
            //for the user to see;
            echo "Something went wrong, please try again later";
        }    }
}