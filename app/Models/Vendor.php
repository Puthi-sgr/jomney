<?php

namespace App\Models;
use App\Core\Database;
use PDO;

class Vendor
{
    private PDO $db;

    public function __construct()
    {
         $this->db = Database::getConnection(); 
    }

    public function all(): array
    {
        $stmt = $this->db->query("SELECT * FROM vendor");
        $results = $stmt->fetchAll();
        
        // Convert JSON strings back to PHP arrays
        foreach ($results as &$vendor) {
            if (isset($vendor['food_types'])) {
                $vendor['food_types'] = json_decode($vendor['food_types'], true) ?: [];
            }
        }
        
        return $results;
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM vendor WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch();
        
        if ($result && isset($result['food_types'])) {
            // Convert JSON string back to PHP array
            $result['food_types'] = json_decode($result['food_types'], true) ?: [];
        }
        
        return $result ?: null;
    }

    public function create(array $data): int|false
    {
        // Convert PHP array to JSON string
        if (isset($data['food_types']) && is_array($data['food_types'])) {
            $data['food_types'] = json_encode($data['food_types']);
        } else {
            $data['food_types'] = json_encode([]); // Empty JSON array
        }
        
        $sql = "INSERT INTO vendor
                (email, password, name, phone, address, food_types, rating, image)
                VALUES
                (:email, :password, :name, :phone, :address, :food_types, :rating, :image)";
        $stmt = $this->db->prepare($sql);

        $result = $stmt->execute([
            'email'       => $data['email'],            
            'password'    => password_hash($data['password'], PASSWORD_DEFAULT),
            'name'        => $data['name'],
            'phone'       => $data['phone'] ?? null,
            'address'     => $data['address'] ?? null,
            'food_types'  => $data['food_types'], // Now JSON string
            'rating'      => $data['rating'] ?? 0,
            'image'       => $data['image'] ?? null,
        ]);

        return $result ? (int)$this->db->lastInsertId() : false;
    }

    public function imageUpdate(int $vendorId, array $data): bool
    {
        $sql = "UPDATE vendor SET image = :image WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'id'    => $vendorId,
            'image' => $data['image'],
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
                // Special handling for food_types array
                if ($field === 'food_types' && is_array($data[$field])) {
                    $params[$field] = json_encode($data[$field]);
                } else {
                    $params[$field] = $data[$field];
                }
                $fields[] = "$field = :$field";
            }
        }
        
        if (empty($fields)) {
            return true;
        }
        
        $sql = "UPDATE vendor SET " . implode(', ', $fields) . " WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM vendor WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}