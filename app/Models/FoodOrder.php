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
          public function getFoodDetailByOrderId(int $orderId): array
          {
             
              $sql = "SELECT fo.*, f.name, f.description, f.price, f.vendor_id
                      FROM food_order fo
                      JOIN food f ON fo.food_id = f.id
                      WHERE fo.order_id = :order_id";
              $stmt = $this->db->prepare($sql);
              $stmt->execute(['order_id' => $orderId]);
              return $stmt->fetchAll(PDO::FETCH_ASSOC);
          }

    public function allOrdersByVendor(int $vendorId): array
    {
        $sql = "SELECT fo.*, f.name, f.description, f.price, f.vendor_id, o.created_at, os.key AS status_key
                FROM orders o
                JOIN order_statuses os ON os.id = o.status_id
                JOIN food_order fo ON fo.order_id = o.id
                JOIN food f ON f.id = fo.food_id
                WHERE f.vendor_id = :vendor_id";
               
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['vendor_id' => $vendorId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get the total count of food orders for a vendor.
     * This is more efficient than fetching all order details.
     */
    public function countOrdersByVendor(int $vendorId): int
    {
        $sql = "SELECT COUNT(*) 
                FROM orders o
                JOIN food_order fo ON fo.order_id = o.id
                JOIN food f ON f.id = fo.food_id
                WHERE f.vendor_id = :vendor_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['vendor_id' => $vendorId]);
        return (int) $stmt->fetchColumn();
    }
    
    public function totalByVendor(int $vendorId, string $finalStatusKey = 'accepted'): float
    {
        $sql = "SELECT COALESCE(SUM(fo.price * fo.quantity),0) AS total
                FROM   food_order fo
                JOIN   food        f  ON f.id  = fo.food_id
                JOIN   orders      o  ON o.id  = fo.order_id
                JOIN   order_statuses os ON os.id = o.status_id
                WHERE  f.vendor_id = :vid
                AND  os.key      = :skey";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['vid' => $vendorId, 'skey' => $finalStatusKey]);
        return (float) $stmt->fetchColumn();
    }

    public function perOrderBreakdown(int $vendorId, string $finalStatusKey = 'accepted'): array
    {
        // We want to show, for each order, the food items (name, description, price, quantity/amt) and the gross total for the order.
        // We'll select order info, and aggregate food items per order.
        // To keep it simple, we'll return one row per food item per order, with order info repeated.
        $sql = "SELECT 
                    o.id AS order_id,
                    o.created_at AS placed_at,
                    f.id AS foodId,
                    f.name AS food_name,
                    f.description AS food_description,
                    fo.price AS food_price,
                    fo.quantity AS amount,  
                    SUM(fo.price * fo.quantity) OVER (PARTITION BY o.id) AS gross
                FROM food_order fo
                JOIN food f ON f.id = fo.food_id
                JOIN orders o ON o.id = fo.order_id
                JOIN order_statuses os ON os.id = o.status_id
                WHERE f.vendor_id = :vid
                  AND os.key = :skey
                ORDER BY o.created_at DESC, o.id, f.name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['vid' => $vendorId, 'skey' => $finalStatusKey]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}