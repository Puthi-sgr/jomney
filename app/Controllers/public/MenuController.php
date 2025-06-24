<?php
namespace App\Controllers\Public;

use App\Core\Response;
use App\Models\Food;

class MenuController
{
    private Food $foodModel;

    public function __construct()
    {
        $this->foodModel = new Food();
    }

    /** GET /menu */
    public function index(): void
    {
        $filters = [];
        
        if (isset($_GET['vendor_id'])) {
            $filters['vendor_id'] = $_GET['vendor_id'];
        }
        
        if (isset($_GET['category'])) {
            $filters['category'] = $_GET['category'];
        }

        $foods = $this->foodModel->all($filters);
        Response::success('Foods retrieved successfully', ['foods' => $foods]);
    }

    /** GET /menu/{id} */
    public function show(int $id): void
    {
        $food = $this->foodModel->find($id);
        if (!$food) {
            Response::error('Food not found', [], 404);
            return;
        }
        Response::success('Food retrieved successfully', $food);
    }
}