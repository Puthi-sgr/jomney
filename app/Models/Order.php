<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class Order{
    protected PDO $db;

    public function __construct(){
        //Get a PDO connect from a database wrapper
        $this->db = (new Database())->getConnection();
    }

    public function all():array{
        $stmt = $this->db->query("SELECT * FROM orders");
        $orders = $stmt->fetchAll();
        return $orders;
    }

    // Add this method for the controller's needs
    public function getOrderWithFoodItems(int $orderId): ?array
    {
        $order = $this->find($orderId);
        if (!$order) {
            return null;
        }

        $sql = "SELECT fo.*, f.name, f.images
            FROM food_order fo
            JOIN food f ON fo.food_id = f.id
            WHERE fo.order_id = :order_id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['order_id' => $orderId]);

        $foodItems = $stmt->fetchAll();

        return $foodItems;     
    }
    public function create(int $customerId, int $statusId, float $total, $remarks = null): int
    {
        $sql = "INSERT INTO orders
                (customer_id, status_id, total_amount, remarks)
                VALUES
                (:customer_id, :status_id, :total_amount, :remarks)
                RETURNING id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'customer_id'  => $customerId,
            'status_id'    => $statusId,
            'total_amount' => $total,
            'remarks'      => $remarks,
        ]);
        return (int) $stmt->fetchColumn();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare(
            //we grab the status key and status label in this query through table join
            //now we have access to two additional columns
            "SELECT o.*, 
            os.key AS status_key, 
            os.label AS status_label
            c.name AS customer_name
             FROM orders o
             JOIN order_statuses os ON o.status_id = os.id
             JOIN customer c ON o.customer_id = c.id
             WHERE o.id = :id"
        );
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }
    //Get all orders from a particular user

    public function updateStatus(int $orderId, int $statusId): bool{
        $sql = "UPDATE orders SET status_id = :status_id WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'id' => $orderId,
            'status_id' => $statusId
        ]);
    }
    
}