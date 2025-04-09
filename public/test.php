<?php 
require __DIR__."/../vendor/autoload.php";

\App\Core\Config::load();

try{
    $pdo = new PDO(
        "pgsql:host={$_ENV['DB_HOST']}; dbname={$_ENV['DB_NAME']}",
        $_ENV['DB_USER'],
        $_ENV["DB_PASSWORD"]
    );
    echo "Database connected successfully";
}catch(PDOException $e){
    die("Connection failed: " . $e->getMessage());
}