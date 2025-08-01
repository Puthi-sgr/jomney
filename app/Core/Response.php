<?php

namespace App\Core;

class Response{
    private array $data;
    private int $status;

    public function __construct(array $data = [], int $status = 200)
    {
        $this->data = $data;
        $this->status = $status;
    }

    public function json(): void
    {
        http_response_code($this->status);
        header('Content-Type: application/json');
        echo json_encode($this->data, JSON_PRETTY_PRINT);
        return;
    }

    public static function success(string $message, array $data = [], int $status = 200): Response{
        //Construct the JSON header
        return new Response([
            'success' => true,
            'message' => $message,
            'data' => $data, 
        ], $status);
    }

    public static function error(string $message, array $data = [], int $status = 400, $extra = []): Response{
        return new Response(array_merge([
            'success' => false,
            'message' => $message,
            'data' => $data,
        ], $extra), $status);
    }

    public function isSuccessful(): bool
    {
        return $this->status >= 200 && $this->status < 300;
    }

    public function body(): array
    {
        return $this->data;
    }

    public function headers(): array
    {
        return [
            'Content-Type' => 'application/json',
            'Status' => $this->status,
        ];
    }

    public function status (): int
    {
        return $this->status;
    }

    
    public function setStatusCode(int $status): void
    {
        $this->status = $status;
    }

    public function setData(array $data): void
    {
        $this->data = $data;
    }

 
}