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
        // Convert PHP array to PostgreSQL array format
        if (isset($data['food_types']) && is_array($data['food_types'])) {
            $data['food_types'] = '{' . implode(',', array_map(function($type) {
                return '"' . str_replace('"', '\"', $type) . '"';
            }, $data['food_types'])) . '}';
        } else {
            $data['food_types'] = '{}'; // Empty PostgreSQL array
        }
        
        $sql = "INSERT INTO vendor
                (email, password, name, phone, address, food_types, rating, image)
                VALUES
                (:email, :password, :name, :phone, :address, :food_types, :rating, :image)";
        $stmt = $this->db->prepare($sql);

        $result =  $stmt->execute([
            'email'       => $data['email'],            
            'password'    => password_hash($data['password'], PASSWORD_DEFAULT),
            'name'        => $data['name'],
            'phone'       => $data['phone'] ?? null,
            'address'     => $data['address'] ?? null,
            'food_types'  => $data['food_types'] ?? [],   // stored as TEXT[]
            'rating'      => $data['rating'] ?? 0,
            'image'   => $data['image'] ?? null,
        ]);

        return $result ? (int)$this->db->lastInsertId() : false;
        //Returning id specifically to update photos within the store controller

        //Asking what id did it just inserted into the database
    }

    public function imageUpdate(int $vendorId, array $data){
        $sql = "UPDATE vendor
            SET
                image = :image
            WHERE id = :id;
        ";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'id'      => $vendorId,
            'image'   => $data['image'],
        ]);
    }

    public function update(int $id, array $data): bool
    {
        // Build dynamic SQL based on provided fields
        $fields = [];
        $params = ['id' => $id];
        
        $allowedFields = ['name', 'address', 'phone', 'food_types', 'rating', 'image', 'password'];
        
        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "$field = :$field";
                $params[$field] = $data[$field];
            }
        }
        
        // If no fields to update, return true
        if (empty($fields)) {
            return true;
        }
        
        $sql = "UPDATE vendor SET " . implode(', ', $fields) . " WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function delete(int $id):bool{
        $stmt = $this->db->prepare("DELETE FROM vendor WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}