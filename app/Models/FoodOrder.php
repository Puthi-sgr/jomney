<?php
// app/Models/FoodOrder.php
namespace App\Models;

use App\Core\Database;
use PDO;

class FoodOrder
{
    private PDO $db;
    
    public function __construct()
    {
        $this->db = Database::getConnection(); 
    }
    public function create(int $orderId, int $foodId, float $price, float $quantity): bool
    {
        $sql = "INSERT INTO food_order
                (order_id, food_id, price, quantity)
                VALUES
                (:order_id, :food_id, :price, :quantity)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'order_id' => $orderId,
            'food_id'  => $foodId,
            'price'    => $price,
            'quantity' => $quantity,
        ]);
    }
}