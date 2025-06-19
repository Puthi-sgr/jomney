<?php
namespace App\Controllers\Admin;

use App\Core\Response;
use App\Models\Order;
use App\Models\OrderStatus;
use App\Models\FoodOrder;
use App\Models\Food;
use App\Models\Customer;
use App\Models\Vendor;

class AdminOrderController
{
    private Order $orderModel;
    private OrderStatus $statusModel;
    private FoodOrder $foodOrderModel;
    private Food $foodModel;
    private Customer $customerModel;
    private Vendor $vendorModel;                                                                                                                               public function __construct()
    {
        $this->orderModel     = new Order();
        $this->statusModel    = new OrderStatus();
        $this->foodOrderModel = new FoodOrder();
        $this->foodModel      = new Food();
        $this->customerModel  = new Customer();
        $this->vendorModel    = new Vendor();
    }
                                      
    /**
     * GET /api/admin/orders
     * List all orders with optional filters (status_id, customer_id, date range).
     */
    public function index(): void
    {
        // For MVP: return all orders
        $orders = $this->orderModel->all(); 
        Response::success('All orders', ['orders' => $orders], 200);
        return;
    }
    
    /**
     * GET /api/admin/orders/{id}
     * View one order with line‐items.
     */
    public function show(int $orderId): void
    {
        $order = $this->orderModel->find($orderId);
        if (!$order) {
            Response::error('Order not found', [], 404);
            return;
        }

        $foodItems = $this->orderModel->getOrderWithFoodItems($orderId);

         Response::success('Order', ['foodItems' => $foodItems], 200);
        return;
    }

    /**
     * PATCH /api/admin/orders/{id}/status
     * Body: { "status_key": "confirmed" }  (key must exist in order_statuses)
    */

    public function updateStatus(int $orderId): void
    {
        $order = $this->orderModel->find($orderId);
        if (!$order) {
            Response::error('Order not found', [], 404);
            return;
        }

        $body = json_decode(file_get_contents('php://input'), true);
        $newKey = $body['status_key'] ?? '';
        if (!$newKey) {
            Response::error('status_key is required', [], 422);
            return;
        }
    
        // 1) Find new status_id by key
        $newStatus = $this->statusModel->findByKey($newKey);
        if (!$newStatus) {
           Response::error('Invalid status key', [], 422);
           return;
        }

        // 2) Update order status
        $result = $this->orderModel->updateStatus($orderId, $newStatus['id']);

        if(!$result){
            Response::error('Failed to update order status', [], 500);
            return;
        }

        Response::success("Order status updated", [], 200);
        return;
    }
        
}