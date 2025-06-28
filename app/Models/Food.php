<?php
// app/Models/Food.php
namespace App\Models;

use App\Core\Database;
use App\Models\Inventory;
use PDO;

class Food
{
    private PDO $db;
    private Inventory $inventoryModel;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
        $this->inventoryModel = new Inventory();
    }

    public function all($filters = []): array{
        $sql = "SELECT f.*, i.qty_available FROM food f JOIN inventory i ON f.id = i.food_id";        
        $params = [];
        $conditions = [];

        if (!empty($filters)) {

            if (isset($filters['vendor_id'])) {
                $conditions[] = "vendor_id = :vendor_id"; //For query
                $params['vendor_id'] = $filters['vendor_id']; //For execution
            }

            if (isset($filters['category'])) {
                $conditions[] = "category = :category";
                $params['category'] = $filters['category'];
            }

            if (!empty($conditions)) {
                $sql .= " WHERE " . implode(' AND ', $conditions);
                //"WHERE vendor_id = :vendor_id AND category = ":category"
            }
        }
      
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM food WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function allByVendor(int $vendorId): array
    {
        $stmt = $this->db->prepare(
            "SELECT f.*,
                i.qty_available AS qty_available
            FROM food f
            JOIN inventory i ON f.id = i.food_id
            WHERE vendor_id = :vendor_id 
            ORDER BY name"
        );
        $stmt->execute(['vendor_id' => $vendorId]);
        return $stmt->fetchAll();
    }

    public function create(array $data): int|false
    {
        $sql = "INSERT INTO food
                (vendor_id, name, description, category, price, ready_time, rating, image)
                VALUES
                (:vendor_id, :name, :description, :category, :price, :ready_time, :rating, :image)";
        $stmt = $this->db->prepare($sql);

        $result = $stmt->execute([
            'vendor_id'   => $data['vendor_id'],       
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
            'category'    => $data['category'] ?? null,
            'price'       => $data['price'],
            'ready_time'  => $data['ready_time'] ?? null,
            'rating'      => $data['rating'] ?? 0,
            'image'       => $data['image'] ?? null,    // Single image URL
        ]);

        return $result ? (int)$this->db->lastInsertId() : false;
    }

    public function imageUpdate(int $foodId, string $imageUrl): bool {
        $sql = "UPDATE food SET image = :image WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute([
            'id' => $foodId,
            'image' => $imageUrl,
        ]); 
    }

    public function update(int $foodId, array $data): bool{
        $sql = "UPDATE food SET
                vendor_id = :vendor_id,
                name = :name,
                price = :price";
        $params = [
            'vendor_id'   => $data['vendor_id'],
            'name'        => $data['name'],
            'price'       => $data['price'],
        ];
        $conditions = [];

        if(array_key_exists('description', $data)) {
            $conditions[] = "description = :description";
            $params['description'] = $data['description'];
        }
        if(array_key_exists('category', $data)) {
            $conditions[] = "category = :category";
            $params['category'] = $data['category'];
        }
        if(array_key_exists('ready_time', $data)) {
            $conditions[] = "ready_time = :ready_time";
            $params['ready_time'] = $data['ready_time'];
        }
        if(array_key_exists('rating', $data)){
            $conditions[] = "rating = :rating";
            $params['rating'] = $data['rating'];
        }
        if(array_key_exists('image', $data)) {
            $conditions[] = "image = :image";
            $params['image'] = $data['image'];
        }
        
        if(!empty($conditions)) {
            $sql .= ", " . implode(', ', $conditions);
        }
        
        $sql .= " WHERE id = :id";
        $params['id'] = $foodId;

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function delete(int $foodId):bool{
        $stmt = $this->db->prepare("DELETE FROM food WHERE id = :id");
        return $stmt->execute(['id' => $foodId]);
    }
}