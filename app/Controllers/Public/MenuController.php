<?php
namespace App\Controllers\Public;

use App\Core\Response;
use App\Models\Food;
use App\Core\Request;

class MenuController
{
    private Food $foodModel;
    private Request $request;

    public function __construct()
    {
        $this->request = new Request();
        $this->foodModel = new Food();
    }

    /** GET /menu */
    public function index(): Response
    {
        $filters = [];
        
        if (isset($_GET['vendor_id'])) {
            $filters['vendor_id'] = $_GET['vendor_id'];
        }
        
        if (isset($_GET['category'])) {
            $filters['category'] = $_GET['category'];
        }

        $foods = $this->foodModel->all($filters);
        return Response::success('Foods retrieved successfully', ['foods' => $foods]);
    }

    /** GET /menu/{id} */
    public function show(int $id): Response
    {
        $food = $this->foodModel->find($id);
        if (!$food) {
            return Response::error('Food not found', [], 404);
        }
        return Response::success('Food retrieved successfully', $food);
    }
}