<?php

namespace App\Core;
use App\Core\Response;

use Throwable;

class ErrorHandler {
    public static function handleException(Throwable $e): void{
        http_response_code(500);

        if($_ENV['APP_ENV'] === "development"){

            Response::success($e->getMessage(),
            ["line"=> $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode(), 'stackTrace' => $e->getTrace()], 500)->json();
        }else{ 
            //for the user to see;
            echo "Something went wrong, please try again later";
        }    
        return;
    }
}