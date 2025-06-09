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

    public function all(): array{
        $stmt = $this->db->query("SELECT * FROM vendor");
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM vendor WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function create(array $data): int|false
    {
        $sql = "INSERT INTO vendor
                (email, password, name, phone, address, food_types, rating, photo_url)
                VALUES
                (:email, :password, :name, :phone, :address, :food_types, :rating, :photo_url)";
        $stmt = $this->db->prepare($sql);

        $result =  $stmt->execute([
            'email'       => $data['email'],            
            'password'    => password_hash($data['password'], PASSWORD_DEFAULT),
            'name'        => $data['name'],
            'phone'       => $data['phone'] ?? null,
            'address'     => $data['address'] ?? null,
            'food_types'  => $data['food_types'] ?? [],   // stored as TEXT[]
            'rating'      => $data['rating'] ?? 0,
            'photo_url'   => $data['photo_url'] ?? null,
        ]);

        return $result ? (int)$this->db->lastInsertId() : false;
            //Returning id specifically to update photos within the store controller

        //Asking what id did it just inserted into the database
    }

    public function update(int $id, array $data): bool{
        $sql = "UPDATE vendor
            SET 
                name = :name,
                address = :address,
                phone = :phone,
                food_types = :food_types,
                rating = :rating,
                photo_url = :photo_url
            WHERE id = :id;  
        ";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'id'          => $id,
            'name'        => $data['name'],
            'address'     => $data['address'],
            'phone'       => $data['phone'],
            'food_types'  => $data['food_types'],
            'rating'      => $data['rating'],
            'photo_url'   => $data['photo_url'],
        ]);
    }

    public function delete(int $id):bool{
        $stmt = $this->db->prepare("DELETE FROM vendor WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}