<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class User{

    private PDO $db;

    public function __construct(){
         $this->db = Database::getConnection(); 
    }

    public function findByEmail(string $email){
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => $email]);

        return $stmt->fetch() ?: null;
    }

    public function create(string $email, string $hashedPassword):bool{
        $stmt = $this->db->prepare(
            "INSERT INTO users (email, password) VALUES (:email, :password)"
        );
        
        $result = $stmt->execute(['email' => $email, 'password' => $hashedPassword]);

        return $result;
    }
}