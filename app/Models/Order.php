<?php

namespace App\Models;

use App\Core\Database;
use App\Models\Inventory;
use App\Exceptions\OutOfStockException;
use PDO;
use Exception;

class Order{
    protected PDO $db;

    private Inventory $inventoryModel;
    public function __construct(){
        //Get a PDO connect from a database wrapper
        $this->db = (new Database())->getConnection();
        $this->inventoryModel = new Inventory();
    }

    public function all(): array {
        $stmt = $this->db->query("SELECT  
            o.*, 
            os.label AS statusLabel
            FROM orders o
            JOIN order_statuses os ON os.id = o.status_id");
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

        $sql = "SELECT fo.*, f.name, f.image
            FROM food_order fo
            JOIN food f ON fo.food_id = f.id
            WHERE fo.order_id = :order_id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['order_id' => $orderId]);

        $foodItems = $stmt->fetchAll();

        return $foodItems;     
    }


    /**
     * Get food price - ADDED MISSING METHOD
     */
    private function getFoodPrice(int $foodId): float
    {
        $sql = "SELECT price FROM food WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$foodId]);
        
        $price = $stmt->fetchColumn();
        if ($price === false) {
            throw new Exception("Food item {$foodId} not found");
        }
        
        return (float) $price;
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
            os.label AS status_label,
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

    // Add this method for the controller's needs
    
    /**
     * Add food item to order - ADDED MISSING METHOD
     */
    private function addFoodToOrder(int $orderId, int $foodId, int $quantity, float $price): bool
    {
        $sql = "INSERT INTO food_order (order_id, food_id, quantity, price) 
                VALUES (?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$orderId, $foodId, $quantity, $price]);
    }
    
    //lock -> total amount -> create order -> adjust the inventory -> create food_order(many) record
    public function createWithInventory(int $customerId, array $items, ?string $remarks = null): int
    {
        //Temporary draft the inventory
        $this->db->beginTransaction();
        
        
        try {
            //initial price amount
            $totalAmount = 0;
            //Items that are available in stock
            $validatedItems = [];

            // 1. Lock and validate all items first
            // Grab each food item
            foreach ($items as $item) {
            
                $foodId = $item['food_id'];
                $quantity = $item['quantity'];
                
                // Lock the inventory row
                $row = $this->inventoryModel->lock($foodId);
                
                if (!$row) {
                    throw new OutOfStockException("Food item {$foodId} not found in inventory");
                }

                if ($row['qty_available'] < $quantity) {
                    throw new OutOfStockException("Insufficient stock for food item {$foodId}. Available: {$row['qty_available']}, Requested: {$quantity}");
                }

                // Get food price for total calculation
                $foodPrice = $this->getFoodPrice($foodId);
                $totalAmount += $foodPrice * $quantity;
                
                $validatedItems[] = [
                    'food_id' => $foodId,
                    'quantity' => $quantity,
                    'price' => $foodPrice
                ];
            }

            // 2. Create the order (status_id = 1 for "pending")
            //This is the part where we create the order

            //The order is done
            $orderId = $this->create($customerId, 1, $totalAmount, $remarks);


            //Now we create the order detail record to view and stuff
            // 3. Adjust inventory and create food_order records
            foreach ($validatedItems as $item) {
                // Deduct the amount of food
                $adjustResult = $this->inventoryModel->adjust($item['food_id'], -$item['quantity']);
                if (!$adjustResult) {
                    throw new Exception("Failed to adjust inventory for food item {$item['food_id']}");
                }
                
                // After deduction we add the food order detail
                $addResult = $this->addFoodToOrder($orderId, $item['food_id'], $item['quantity'], $item['price']);
                if (!$addResult) {
                    throw new Exception("Failed to add food item {$item['food_id']} to order {$orderId}");
                }
            }

            //Executes the models function
            $this->db->commit();
            return $orderId;

        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

}