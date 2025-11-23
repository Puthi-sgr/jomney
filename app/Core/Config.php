<?php

namespace App\Core;

use Dotenv\Dotenv;

class Config
{
    public static function load(): void
    {

        $root = dirname(__DIR__, 2); // points to repo root (where .env would be normally)

        try {
            // safeLoad() does not throw if the file is missing (works well on Render)
            Dotenv::createImmutable($root)->safeLoad();
        } catch (\Throwable $e) {
            // Do not fail hard if dotenv is missing — rely on runtime env vars provided by the host
            // Optionally log to help debugging in dev.
            error_log("Dotenv load warning: " . $e->getMessage());
        }
    }
}