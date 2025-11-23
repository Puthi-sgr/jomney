<?php
namespace App\Controllers\Public;

use App\Core\Response;
use App\Models\Vendor;
use App\Models\Food;
use App\Core\Request;

class PublicController
{
    private Vendor $vendorModel;
    private Food $foodModel;
    private Request $request;

    public function __construct()
    {
        $this->request = new Request();
        $this->vendorModel = new Vendor();
        $this->foodModel = new Food();
    }

    /**
     * GET /api/public/vendors
     * Fetch all vendors for main application listing
     */
    public function getAllVendors(): Response
    {
        $vendors = $this->vendorModel->all();
        $vendorIds = array_column($vendors, 'id');
        $foodsByVendor = $this->foodModel->allByVendorIds($vendorIds);

        // Remove sensitive information for public access
        foreach ($vendors as &$vendor) {
            unset($vendor['email'], $vendor['password']);
            $vendorFoods = $foodsByVendor[$vendor['id']] ?? [];

            foreach ($vendorFoods as &$food) {
                $food['stock_qty'] = isset($food['qty_available']) ? (int) $food['qty_available'] : 0;
                unset($food['qty_available'], $food['vendor_id'], $food['created_at'], $food['updated_at']);
            }
            unset($food); // break reference

            $vendor['foods'] = $vendorFoods;
        }
        unset($vendor); // break reference

        return Response::success('All vendors retrieved',['vendors' => $vendors]);
    }

    /**
     * GET /api/public/foods
     * Fetch all foods for main application listing
     */
    public function getAllFoods(): Response
    {
        $foods = $this->foodModel->allWithVendorInfo();
        if (!$foods) {
            return Response::error('No foods found', [], 404);
        }

        foreach ($foods as &$food) {
            $food['stock_qty'] = isset($food['qty_available']) ? (int) $food['qty_available'] : 0;
            $food['vendor'] = [
                'id' => (int) $food['vendor_id'],
                'name' => $food['vendor_name'],
                'address' => $food['vendor_address'],
                'phone' => $food['vendor_phone'],
                'rating' => $food['vendor_rating'],
                'image' => $food['vendor_image'],
            ];

            unset(
                $food['qty_available'],
                $food['vendor_id'],
                $food['vendor_name'],
                $food['vendor_address'],
                $food['vendor_phone'],
                $food['vendor_rating'],
                $food['vendor_image'],
                $food['created_at'],
                $food['updated_at']
            );
        }
        unset($food); // break reference

        return Response::success('All foods retrieved', ['foods' => $foods]);
    }

    /**
     * GET /api/public/vendors/{id}
     * Fetch vendor details with their food list
     */
    public function getVendorDetails(int $vendorId): Response
    {
        // Get vendor details
        $vendor = $this->vendorModel->find($vendorId);
        if (!$vendor) {
            return Response::error('Vendor not found', [], 404);
        }

        // Remove sensitive information
        unset($vendor['email'], $vendor['password'], $vendor['created_at']);

        // Get all foods for this vendor
        $foods = $this->foodModel->allByVendor($vendorId);
        // Remove sensitive/unnecessary fields from each food
        foreach ($foods as &$food) {
            $food['stock_qty'] = isset($food['qty_available']) ? (int) $food['qty_available'] : 0;
            unset($food['qty_available'], $food['created_at'], $food['updated_at'], $food['vendor_id']);
        }
        unset($food); // break reference

        // Combine vendor details with their foods
        $vendorWithFoods = [
            'vendor' => $vendor,
            'foods' => $foods
        ];

        return Response::success('Vendor details with foods', $vendorWithFoods);
    }

    /**
     * GET /api/public/foods/{id}
     * Fetch specific food details
     */
    public function getFoodDetails(int $foodId): Response
    {
        $food = $this->foodModel->find($foodId);
        if (!$food) {
            return Response::error('Food not found', [], 404);
        }
    
        $vendorId = $food["vendor_id"];

        $food['stock_qty'] = isset($food['qty_available']) ? (int) $food['qty_available'] : 0;
        unset($food["vendor_id"], $food['qty_available'], $food['created_at'], $food['updated_at']);

        $vendor = $this->vendorModel->find($vendorId);
        unset($vendor['email'], $vendor['password'], $vendor['created_at'], $vendor['updated_at']);
        
        $food['vendor'] = $vendor;

        return Response::success('Food details', $food);
    }
}
