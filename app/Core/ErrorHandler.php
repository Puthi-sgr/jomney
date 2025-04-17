<?php

namespace App\core;

use Throwable;

class ErrorHandler {
    public static function handleException(Throwable $e){
        http_response_code(500);

        if($_ENV['APP_ENV'] === "development"){
            echo "<pre style='background-color: #f8f9fa; border: 1px solid #ced4da; border-radius: 0.25rem;'>";
            echo "❌ Uncaught Exception: <span style='color: #e74c3c;'>" . $e->getMessage() . "</span>\n";
            echo "📍 In: <span style='color: #007bff;'>" . $e->getFile() . "</span> on line <span style='color: #007bff;'>" . $e->getLine() . "</span>\n";
            echo "🧵 Stack Trace:\n" . $e->getTraceAsString();
            echo "</pre>";
        }else{
            //for the user to see;
            echo "Something went wrong, please try again later";
        }
    }
}