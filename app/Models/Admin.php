<?php
// app/Models/Admin.php
namespace App\Models;
use App\Core\Database;
use PDO;

class Admin
{
    private PDO $db;
    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM admin WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null; //This is the part where we get the actual data
    }

    public function findByEmail(string $email): ?array{
        $stmt = $this->db->prepare("SELECT * FROM admin WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => $email]);
   
        return $stmt->fetch() ?: null;
    }
    public function create(array $data): bool
    {
        $sql = "INSERT INTO admin (email, password, name, is_super)
                VALUES (:email, :password, :name, :is_super)";
        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            'email'     => $data['email'],            
            'password'  => password_hash($data['password'], PASSWORD_DEFAULT),
            'name'      => $data['name'],
            'is_super'  => $data['is_super'] ?? false,
        ]);
    }
}