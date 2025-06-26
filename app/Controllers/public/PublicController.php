<?php
namespace App\Controllers\Public;

use App\Core\Response;
use App\Models\Vendor;
use App\Models\Food;

class PublicController
{
    private Vendor $vendorModel;
    private Food $foodModel;

    public function __construct()
    {
        $this->vendorModel = new Vendor();
        $this->foodModel = new Food();
    }

    /**
     * GET /api/public/vendors
     * Fetch all vendors for main application listing
     */
    public function getAllVendors(): void
    {
        $vendors = $this->vendorModel->all();
        
        // Remove sensitive information for public access
        foreach ($vendors as &$vendor) {
            unset($vendor['email'], $vendor['password']);
        }
        
        Response::success('All vendors retrieved', $vendors);
    }

    /**
     * GET /api/public/foods
     * Fetch all foods for main application listing
     */
    public function getAllFoods(): void
    {
        $foods = $this->foodModel->all();
        Response::success('All foods retrieved', $foods);
    }

    /**
     * GET /api/public/vendors/{id}
     * Fetch vendor details with their food list
     */
    public function getVendorDetails(int $vendorId): void
    {
        // Get vendor details
        $vendor = $this->vendorModel->find($vendorId);
        if (!$vendor) {
            Response::error('Vendor not found', [], 404);
            return;
        }

        // Remove sensitive information
        unset($vendor['email'], $vendor['password']);

        // Get all foods for this vendor
        $foods = $this->foodModel->allByVendor($vendorId);

        // Combine vendor details with their foods
        $vendorWithFoods = [
            'vendor' => $vendor,
            'foods' => $foods
        ];

        Response::success('Vendor details with foods', $vendorWithFoods);
    }

    /**
     * GET /api/public/foods/{id}
     * Fetch specific food details
     */
    public function getFoodDetails(int $foodId): void
    {
        $food = $this->foodModel->find($foodId);
        if (!$food) {
            Response::error('Food not found', [], 404);
            return;
        }

        Response::success('Food details', $food);
    }
}
