<?php
// app/Models/Inventory.php
namespace App\Models;

use App\Core\Database;
use PDO;

class Inventory{


    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection(); 
    }

    /* seed row when a food item is first created */
    public function create(int $foodId, int $initialQty = 0): bool
    {
        //Food id is grabbed when the food has been created
        //Qty available is the initial qty
        $stmt = $this->db->prepare(
            "INSERT INTO inventory (food_id, qty_available) VALUES (:fid, :qty)"
        );
        return $stmt->execute(['fid' => $foodId, 'qty' => $initialQty]);
    }

    /* row-level lock during checkout */
    public function lock(int $foodId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT qty_available FROM inventory WHERE food_id = :fid FOR UPDATE"
        );
        $stmt->execute(['fid' => $foodId]);
        return $stmt->fetch() ?: null;
    }

      /* + / – stock */
    public function adjust(int $foodId, int $delta): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE inventory
               SET qty_available = qty_available + :d,
                   updated_at    = NOW()
             WHERE food_id = :fid"
        );
        return $stmt->execute(['d' => $delta, 'fid' => $foodId]);
    }

     /**
     * Get current stock level
     */
    public function getStock(int $foodId): int
    {
        $sql = "SELECT i.qty_available, f.name FROM inventory i JOIN food f ON i.food_id = f.id WHERE i.food_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$foodId]);
        return $stmt->fetchColumn();
    }

     /**
     * Set stock level directly
     */
    public function setStock(int $foodId, int $quantity): bool
    {
        $sql = "INSERT INTO inventory (food_id, qty_available) 
                VALUES (?, ?) 
                ON CONFLICT (food_id) 
                DO UPDATE SET qty_available = EXCLUDED.qty_available, 
                             updated_at = CURRENT_TIMESTAMP";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$foodId, $quantity]);
    }
}