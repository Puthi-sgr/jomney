<?php
namespace App\Models;

use App\Core\Database;
use PDO;

class MenuItem{
    private PDO $db;

    public function __construct(){
        $this->db = (new Database()) -> getConnection();
    }

    public function all():array {
        $stmt = $this->db->query(
            "SELECT id, name, description, price, created_at, updated_at
             FROM menu_items
             ORDER BY name ASC"
        );

        return $stmt->fetchAll();
    }

    public function create(string $name, string $description, float $price):bool{

        $sql = "INSERT INTO menu_items (name, description, price)
                VALUES (:name, :description, :price)";
        $stmt = $this->db->prepare($sql);

        $result = $stmt->execute([
            'name' => $name,
            'description' => $description,
            'price' => $price
        ]);

        return $result;
    }
}