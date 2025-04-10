<?php

namespace App\Core;

use PDO;
use PDOException;

class Database
{
    private PDO $connection;

    public function __construct()
    {
        $host = $_ENV['DB_HOST'] ?? 'postgres';
        $db   = $_ENV['DB_NAME'] ?? 'fooddb';
        $user = $_ENV['DB_USER'] ?? 'fooduser';
        $pass = $_ENV['DB_PASS'] ?? 'foodpass';

        $dsn = "pgsql:host=$host;dbname=$db;";

        try {
            $this->connection = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (PDOException $e) {
            die("DB connection failed: " . $e->getMessage());
        }
    }

    public function getConnection(): PDO
    {
        return $this->connection;
    }
}
