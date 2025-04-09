<?php

namespace App\Core;

use Dotenv\Dotenv;

class Config {
    public static function load(): void {
        $dotenv = Dotenv::createImmutable(__DIR__.'/../../');
        $dotenv->load();
        
        // Validate required variables
        $dotenv->required([
            'DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASSWORD'
        ]);
    }
}