<?php
namespace App\Controllers\Customer;

use App\Core\Response;
use App\Models\Order;

class CustomerOrderController
{
    private Order $orderModel;

    public function __construct() 
    {
        $this->orderModel = new Order();
    }

    /** POST /orders */
    public function store(): void
    {
        $body = json_decode(file_get_contents('php://input'), true) ?? [];

        $items = $body['items'] ?? [];
        $remarks = $body['remarks'] ?? '';
        $customerId = (int) ($_SERVER['user_id'] ?? 0);

        if (empty($body['items']) || !is_array($body['items'])) {
            Response::error('Items are required and must be an array', [], 422);
            return;
        }

        $order = $this->orderModel->createWithInventory($customerId, $items, $remarks ?? null);
        Response::success('Order created', ['orders' => $order], 201);
    }

    /** GET /orders */
    public function index(): void
    {
        $customerId = (int) ($_SERVER['user_id'] ?? 0);
        
        $orders = $this->orderModel->getOrderHistory($customerId);
        Response::success('Orders retrieved', ['orders' => $orders], 200);
    }

    /** GET /orders/{id} */
    public function show(int $id): void
    {
        $customerId = (int) ($_SERVER['user_id'] ?? 0);
        
        $order = $this->orderModel->findOrderForCustomer($id, $customerId);
        if (!$order) {
            Response::error('Order not found', [], 404);
            return;
        }
        Response::success('Order retrieved', $order);
    }

    /** DELETE /orders/{id} */
    public function cancel(int $id): void
    {
        $customerId = (int) ($_SERVER['user_id'] ?? 0);
        
        $order = $this->orderModel->cancelIfPending($id, $customerId);
        if (!$order) {
            Response::error('Cannot cancel order', ['cancelled order' => $order], 422);
            return;
        }
        Response::success('Order cancelled', $order);
    }
}
