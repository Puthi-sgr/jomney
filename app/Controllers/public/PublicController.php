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
            $vendor['foods'] = $this->foodModel->allByVendor($vendor['id']);
            // Remove sensitive fields from each food item
            foreach ($vendor['foods'] as &$food) {
                unset($food['vendor_id'], $food['created_at'], $food['updated_at']);
            }
            unset($food); // break reference
        }
        unset($vendor); // break reference
        
        Response::success('All vendors retrieved',['vendors' => $vendors]);
    }

    /**
     * GET /api/public/foods
     * Fetch all foods for main application listing
     */
    public function getAllFoods(): void
    {
        $foods = $this->foodModel->all();
        if(!$foods) {
            Response::error('No foods found', [], 404);
            return;
        }


        foreach($foods as &$food){
            $vendorId = $food["vendor_id"];
            $vendor = $this->vendorModel->find($vendorId);
            unset($vendor['email'], $vendor['password']);
            $food['vendor'] = $vendor;
            unset($food['vendor_id']);
            unset($food['created_at']);
            unset($food['updated_at']);
            unset($food['vendor']['created_at']);
            unset($food['vendor']['updated_at']);
     
        }

        Response::success('All foods retrieved', ['foods' => $foods]);
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
        unset($vendor['email'], $vendor['password'], $vendor['created_at']);

        // Get all foods for this vendor
        $foods = $this->foodModel->allByVendor($vendorId);
        // Remove sensitive/unnecessary fields from each food
        foreach ($foods as &$food) {
            unset($food['created_at'], $food['updated_at'], $food['vendor_id']);
        }
        unset($food); // break reference

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
    
        $vendorId = $food["vendor_id"];

        unset($food["vendor_id"], $food['created_at'], $food['updated_at']);

        $vendor = $this->vendorModel->find($vendorId);
        unset($vendor['email'], $vendor['password'], $vendor['created_at'], $vendor['updated_at']);
        
        $food['vendor'] = $vendor;

        Response::success('Food details', $food);
    }
}
