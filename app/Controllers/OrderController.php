<?php
namespace App\Controllers;

use App\Core\Response;
use App\Models\Order;

class OrderController{
    private Order $orderModel;

    public function __construct(){
        //instantiate the order model
        $this->orderModel  = new Order();
    }

    public function index(): void{

        $userId = (int) ($_SERVER('user_id') ?? 0);

        //Fetch orders from model;
        $orders = $this->orderModel->getByUser($userId);

        Response::success("Orders retrieved", $orders, 200);
    }

    public function create(): void{

        //Decode the user payload and store it in object

        //Wrong -> grab the user ID from the payload object
        $userId = (int) ($_SERVER['user_id'] ?? 0);

        //Read raw JSON
        $body = json_decode(file_get_contents('php://input'), true);

         // Basic validation
        if (empty($body['items']) || !is_array($body['items'])) {
            Response::error('Invalid or missing items', [], 422);
        }
        if (empty($body['total']) || !is_numeric($body['total'])) {
            Response::error('Invalid or missing total',[], 422);
        }

        $items = $body['items'];
        $totalPrice = $body['total'];

        $success = $this->orderModel->create($userId, $items, $totalPrice);

         if ($success) {
            Response::success('Order created');
        } else {
            Response::error('Failed to create order',[], 500);
        }
    }
}