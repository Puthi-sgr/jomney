<?php 
require __DIR__."/../vendor/autoload.php";

\App\Core\Config::load();

try{
    // Use the centralized Database class instead of direct PDO
    $pdo = \App\Core\Database::getConnection();
    echo "Database connected successfully with connection pooling";
    
}catch(Exception $e){
    die("Connection failed: " . $e->getMessage());
}