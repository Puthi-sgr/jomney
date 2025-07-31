<?php

namespace App\Core;
use App\Core\Response;

use Throwable;

class ErrorHandler {
    public static function handleException(Throwable $e): Response{
        http_response_code(500);

        if($_ENV['APP_ENV'] === "development"){

            $response = Response::success($e->getMessage(), 
            ["line"=> $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode(), 'stackTrace' => $e->getTrace()], 500);
        }else{ 
            //for the user to see;
            echo "Something went wrong, please try again later";
        }    
        return $response->json();
    }
}