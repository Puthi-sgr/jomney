<?php
// app/Models/Food.php
namespace App\Models;

use App\Core\Database;
use PDO;

class Food
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
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
            "SELECT * FROM food WHERE vendor_id = :vendor_id ORDER BY name"
        );
        $stmt->execute(['vendor_id' => $vendorId]);
        return $stmt->fetchAll();
    }

    public function create(array $data): bool
    {
        $sql = "INSERT INTO food
                (vendor_id, name, description, category, price, ready_time, rating, images)
                VALUES
                (:vendor_id, :name, :description, :category, :price, :ready_time, :rating, :images)";
        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            'vendor_id'   => $data['vendor_id'],       
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
            'category'    => $data['category'] ?? null,
            'price'       => $data['price'],
            'ready_time'  => $data['ready_time'] ?? null,
            'rating'      => $data['rating'] ?? 0,
            'images'      => $data['images'] ?? [],    // TEXT[] array of URLs
        ]);
    }
}