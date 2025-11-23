<?php

namespace App\Core;
use App\Core\Response;

use Throwable;

class ErrorHandler {
    public static function handleException(\Throwable $exception): void
    {
        // Default to 'production' if not set
        $env = $_ENV['APP_ENV'] ?? 'production';
        
        if ($env === 'development') {
            // Show detailed error
            http_response_code(500);

            Response::error($exception->getMessage(),
            ["line"=> $exception->getLine(), 'file' => $exception->getFile(), 'code' => $exception->getCode(), 'stackTrace' => $exception->getTrace()], 500)->json();
        }else{ 
            //for the user to see;
            echo "Something went wrong, please try again later";
        }    
        return;
    }
}