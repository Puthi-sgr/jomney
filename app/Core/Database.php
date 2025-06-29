<?php

namespace App\Core;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $connection = null; 
    private static int $connectionCount = 0;

    private function __construct() {}

    public static function getConnection(): PDO
    {
        if (self::$connection === null) {
            $host = $_ENV['DB_HOST'] ?? 'db';
            $db   = $_ENV['DB_NAME'] ?? 'food_delivery';
            $user = $_ENV['DB_USER'] ?? 'food_user';
            $pass = $_ENV['DB_PASS'] ?? 'secure_password'; 

            $dsn = "pgsql:host=$host;dbname=$db;";

            try {
                self::$connection = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_PERSISTENT => true, // Enable connection pooling
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
                self::$connectionCount++;
                error_log("Database connection established (Pool count: " . self::$connectionCount . ")");
            } catch (PDOException $e) {
                error_log("DB connection failed: " . $e->getMessage());
                throw new \Exception("Database connection failed");
            }
        }

        return self::$connection;
    }

    // Optional: Method to close connection (useful for testing)
    public static function closeConnection(): void
    {
        self::$connection = null;
    }

    // Get connection pool statistics
    public static function getConnectionStats(): array
    {
        return [
            'pool_enabled' => true,
            'connection_count' => self::$connectionCount,
            'is_connected' => self::$connection !== null
        ];
    }
}
