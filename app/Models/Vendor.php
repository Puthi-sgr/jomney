<?php

namespace App\Models;
use App\Core\Database;
use PDO;

class Vendor
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM vendor WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

     public function create(array $data): bool
    {
        $sql = "INSERT INTO vendor
                (email, password, name, phone, address, food_types, rating, photo_url)
                VALUES
                (:email, :password, :name, :phone, :address, :food_types, :rating, :photo_url)";
        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            'email'       => $data['email'],            
            'password'    => password_hash($data['password'], PASSWORD_DEFAULT),
            'name'        => $data['name'],
            'phone'       => $data['phone'] ?? null,
            'address'     => $data['address'] ?? null,
            'food_types'  => $data['food_types'] ?? [],   // stored as TEXT[]
            'rating'      => $data['rating'] ?? 0,
            'photo_url'   => $data['photo_url'] ?? null,
        ]);
    }
}