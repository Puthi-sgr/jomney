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
            // Check if we should use PgBouncer
            $usePgBouncer = $_ENV['USE_PGBOUNCER'] === 'true';
            
            if ($usePgBouncer) {
                $host = $_ENV['PGBOUNCER_HOST'] ?? 'pgbouncer';
                $port = $_ENV['PGBOUNCER_PORT'] ?? '6432';
            } else {
                $host = $_ENV['DB_HOST'] ?? 'db';
                $port = '5432';
            }
            
            $dbname = $_ENV['DB_NAME'] ?? 'food_delivery';
            $username = $_ENV['DB_USER'] ?? 'food_user';
            $password = $_ENV['DB_PASS'] ?? 'secure_password';

            $dsn = "pgsql:host={$host};port={$port};dbname={$dbname}";

            try {
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ];

                // Only use persistent connections when NOT using PgBouncer
                // PgBouncer handles pooling, so we don't need PDO persistence
                if (!$usePgBouncer) {
                    $options[PDO::ATTR_PERSISTENT] = true;
                }

                self::$connection = new PDO($dsn, $username, $password, $options);
                
                error_log("Database connected via: " . ($usePgBouncer ? 'PgBouncer' : 'Direct'));
                
            } catch (PDOException $e) {
                error_log("Database connection failed: " . $e->getMessage());
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
