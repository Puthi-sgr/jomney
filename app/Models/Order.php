<?php

namespace App\Models;

use App\Core\Database;
use App\Models\Inventory;
use App\Models\FoodOrder;
use App\Models\OrderStatus;
use App\Models\Customer;

use App\Exceptions\OutOfStockException;
use PDO;
use Exception;

class Order{
    protected PDO $db;

    private Inventory $inventoryModel;
    private FoodOrder $foodOrderModel;
    private Customer $customerModel;
    private OrderStatus $statusModel;
    public function __construct(){
        //Get a PDO connect from a database wrapper
        $this->db = (new Database())->getConnection();
        $this->inventoryModel = new Inventory();
        $this->foodOrderModel = new FoodOrder();
        $this->customerModel  = new Customer();
        $this->statusModel = new OrderStatus();
    }

    public function all(): array {

        $stmt = $this->db->query("SELECT  
            o.*, 
            c.name AS customerName,
          
            os.label AS statusLabel  
            FROM orders o
            JOIN order_statuses os ON os.id = o.status_id
            JOIN customer c on o.customer_id = c.id

            ");        
            
        $stmt->execute();        
        $orders = $stmt->fetchAll();
        return $orders;
    }
    // Add this method for the controller's needs
    public function getOrderWithFoodItems(int $orderId): ?array
    {
        $order = $this->find($orderId);
        $status = $this->statusModel->findByKey($order['status_key']);
        $customer = $this->customerModel->find($order['customer_id']);
        $order['status'] = $status;
        $order['customer'] = $customer;
        if (!$order) {
            return null;
        }

        $sql = "SELECT 
            fo.*, 
            f.name, 
            f.image
            FROM food_order fo
            JOIN food f ON fo.food_id = f.id
            WHERE fo.order_id = :order_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['order_id' => $orderId]);

        $foodItems = $stmt->fetchAll();
        
        $order['food_detail'] = $foodItems;

        return $order;     
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

    public function find(int $orderId): ?array
    {
        $stmt = $this->db->prepare(
            //we grab the status key and status label in this query through table join
            //now we have access to two additional columns
            "SELECT o.*, 
            os.key AS status_key, 
            os.label AS status_label
            FROM orders o
            JOIN order_statuses os ON o.status_id = os.id
            WHERE o.id = :id"
        );
        $stmt->execute(['id' => $orderId]);
        return $stmt->fetch() ?: null;
    }

    public function findOrderForCustomer(int $orderId, int $customerId): ?array
    {
        $sql = "SELECT o.*, 
                    os.key AS status_key, 
                    os.label AS status_label,
                    c.name AS customer_name
                FROM orders o
                JOIN order_statuses os ON o.status_id = os.id
                JOIN customer c ON o.customer_id = c.id
                WHERE o.id = :order_id AND o.customer_id = :customer_id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'order_id' => $orderId,
            'customer_id' => $customerId
        ]);
        
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }
    
    public function getOrderHistoryByPage(int $customerId, int $page = 1){
        //The point is to calculate only the necessary order result
        $limit = 10;                           // 10 orders per page
        $offset = ($page - 1) * $limit;        // Skip previous pages
        
        $sql = "SELECT * FROM orders 
                WHERE customer_id = ? 
                ORDER BY created_at DESC 
                LIMIT ? OFFSET ?";             // Only get 10 records
                
        // Page 1: LIMIT 10 OFFSET 0  (orders 1-10)
        // Page 2: LIMIT 10 OFFSET 10 (orders 11-20)  
        // Page 3: LIMIT 10 OFFSET 20 (orders 21-30)
    }

    public function getOrderHistory(int $customerId): ?array{

        $sql = "SELECT * FROM orders 
                WHERE customer_id = :id 
                ORDER BY created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([    
            'id' => $customerId
        ]);

        return $stmt->fetchAll() ?: null;
                
    }

    public function updateStatus(int $orderId, int $statusId): bool{

        $sql = "UPDATE orders SET status_id = :status_id WHERE id = :id";

        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute([
            'id' => $orderId,
            'status_id' => $statusId
        ]);
    }

    

    //lock -> total amount -> create order -> adjust the inventory -> create food_order(many) record

    /* 
        $items = [
            [
                'food_id' => 5,      // Pizza ID
                'quantity' => 2      // Customer wants 2 pizzas
            ],
            [
                'food_id' => 12,     // Burger ID  
                'quantity' => 1      // Customer wants 1 burger
            ]
        ];
    */

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
                $addResult = $this->foodOrderModel->create($orderId, $item['food_id'], $item['price'], $item['quantity']);
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

    public function cancelIfPending(int $orderId, int $customerId): ?array
    {
        // Start transaction since we might need to restore inventory
        $this->db->beginTransaction();
        
        try {
            // First, find the order and check if it belongs to customer AND is pending
            $sql = "SELECT o.*, os.key AS status_key 
                    FROM orders o
                    JOIN order_statuses os ON o.status_id = os.id
                    WHERE o.id = :order_id 
                    AND o.customer_id = :customer_id 
                    AND os.key = 'pending'";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'order_id' => $orderId,
                'customer_id' => $customerId
            ]);
            
            $order = $stmt->fetch();
            
            // If order doesn't exist, not owned by customer, or not pending
            if (!$order) {
                $this->db->rollBack();
                return null;
            }
            
            // Get the "cancelled" status ID
            $cancelledStatusSql = "SELECT id FROM order_statuses WHERE key = 'cancelled'";
            $cancelledStmt = $this->db->prepare($cancelledStatusSql);
            $cancelledStmt->execute();
            $cancelledStatusId = $cancelledStmt->fetchColumn();
            
            if (!$cancelledStatusId) {
                throw new Exception("Cancelled status not found");
            }
            
            // Update order status to cancelled
            $updateSql = "UPDATE orders 
                        SET status_id = :status_id, updated_at = NOW() 
                        WHERE id = :order_id";
            //Update to the cancelled status id
            $updateStmt = $this->db->prepare($updateSql);
            $success = $updateStmt->execute([
                'status_id' => $cancelledStatusId,
                'order_id' => $orderId
            ]);
            
            if (!$success) {
                throw new Exception("Failed to update order status");
            }
            
            // Optional: Restore inventory (if you want to put items back in stock)
            $this->restoreInventoryForOrder($orderId);
            
            $this->db->commit();
            
            // Return the updated order
            return $this->findOrderForCustomer($orderId, $customerId);
            
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }       

    // Helper method to restore inventory when order is cancelled
    private function restoreInventoryForOrder(int $orderId): void
    {
        // Get all food items from this order
        $sql = "SELECT food_id, quantity FROM food_order WHERE order_id = :order_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['order_id' => $orderId]);
        $foodItems = $stmt->fetchAll();
        
        // Restore inventory for each item
        foreach ($foodItems as $item) {
            $this->inventoryModel->adjust($item['food_id'], $item['quantity']); // Add back to inventory
        }
    }

}