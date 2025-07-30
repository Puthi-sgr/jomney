<?php
namespace App\Controllers\Customer;

use App\Core\Response;
use App\Models\Order;
use App\Models\Customer;
use App\Models\FoodOrder;
use App\Models\OrderStatus;
use App\Models\Vendor;
use App\Core\Request;

class CustomerOrderController
{
    private Order $orderModel;
    private Customer $customerModel;
    private FoodOrder $foodOrderModel;
    private OrderStatus $orderStatusModel;
    private Vendor $vendorModel;
    private Request $request;

    public function __construct()
    {
        $this->request = new Request();
        $this->orderModel = new Order();
        $this->customerModel = new Customer();
        $this->foodOrderModel = new FoodOrder();
        $this->orderStatusModel = new OrderStatus();
        $this->vendorModel = new Vendor();
    }

    /** POST /orders */
    public function store(): void
    {
        $body = $this->request->all();

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

        $customer = $this->customerModel->find($customerId);
        if (!$customer) {
            Response::error('Customer not found', [], 404);
            return;
        }
        unset($customer['password']);
        unset($customer['created_at']);
        unset($customer['updated_at']);
        
      

        if (!$orders) {
            Response::error('No orders found', [], 404);
        }
        
        $allOrders = [];

        foreach($orders as $order) {
            $orderId = $order['id'];
            $orderStatusId = $order['status_id'];
            $orderStatus = $this->orderStatusModel->findById($orderStatusId);
            $order['order_status'] = $orderStatus;
            $order['food_detail'] = $this->foodOrderModel->getFoodDetailByOrderId($orderId);

            // Remove the 'customer' field from the order
            unset($order['customer']);
            unset($order['customer_id']);
            unset($order['updated_at']);
            unset($order['status_id']);
            unset($order['order_status']['created_at']);
            unset($order['order_status']['updated_at']);

            $allOrders[] = $order;
        }

        Response::success('Orders retrieved', 
                [
                    'customer' => $customer, 
                    'orders' => $allOrders
                ], 
            200);
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

        $food_detail = $this->foodOrderModel->getFoodDetailByOrderId($id);
        // Remove 'order_id' from each food detail item
        foreach ($food_detail as &$item) {
            unset($item['order_id']);
            unset($item['updated_at']);
            $vendor = $this->vendorModel->find($item['vendor_id']);
            unset($item['vendor_id']);
            unset($vendor['email'], $vendor['password'], $vendor['created_at'], $vendor['updated_at']);
            $item['vendor'] = $vendor;
        }
        unset($item); // break the reference
        $order['food_detail'] = $food_detail;
        unset($order['updated_at']);
        unset($order['food_detail']['updated_at']);

        
       

        Response::success('Order retrieved', ['order' => $order]);
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
