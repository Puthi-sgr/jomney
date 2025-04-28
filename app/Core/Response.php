<?php

namespace App\Core;

class Response{
    public static function json(array $data = [], int $status = 200):void{
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_PRETTY_PRINT);
        exit;
    }

    public static function success(string $message, array $data = [], int $status = 200):void{
        //Construct the JSON header
        self::json([
            'success' => true,
            'message' => $message,
            'data' => $data, 
        ], $status);
    }

    public static function error(string $message, array $data = [], int $status = 400, $extra = []):void{
        self::json(array_merge([
            'success' => false,
            'message' => $message,
            'data' => $data,
        ], $extra), $status);
    }
}