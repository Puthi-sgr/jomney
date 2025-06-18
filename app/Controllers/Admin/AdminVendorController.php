<?php
namespace App\Controllers\Admin;

use App\Core\Response;
use App\Traits\ValidationTrait;
use App\Models\Vendor;
use App\Core\CloudinaryService;

class AdminVendorController{

    use ValidationTrait;

    private Vendor $vendorModel;
    private CloudinaryService $cloudinaryService;

    public function __construct(){
        $this->vendorModel = new Vendor();
        $this->cloudinaryService = new CloudinaryService();
    }
    /**
     * GET /api/admin/vendors
     * List all vendors, optionally with pagination or search parameters.
    */
    public function index(): void
    {
        $vendors = $this->vendorModel->all();
        Response::success('Vendors list', $vendors);
        return;
    }

    /**
     * GET /api/admin/vendors/{id}
     * View a single vendor by ID.
     */
    public function show(int $id): void
    {
        $vendor = $this->vendorModel->find($id);
        if (!$vendor) {
            Response::error('Vendor not found', [], 404);
            return;
        }
        Response::success('Vendor details', $vendor);
        return;
    }

    public function foodTypeFormat($foodTypes){
        // Fix: Change condition to check if NOT empty
        if (!empty($foodTypes)) {
            if (is_string($foodTypes)) {
                // Method 1: JSON string
                if (strpos($foodTypes, '[') === 0) {
                    $categories = json_decode($foodTypes, true);
                } else {
                    // Method 2: Comma-separated
                    $categories = explode(',', $foodTypes);
                    $categories = array_map('trim', $categories); // Remove whitespace
                }
            } else {
                // Method 3: Already an array
                $categories = $foodTypes;
            }
            return $categories;
        }
        
        // Return empty array if no food types provided
        return [];
    }
    /**
         * POST /api/admin/vendors
         * Create a new vendor.
         * Body (multipart/form-data or JSON with a image):
         *   { 
         * "email": "...", 
         * "password":"...", 
         * "name":"...", ... 
         * "food_types":["thai","burgers"], "image":"https://..." }
     */

    public function store():void {
        
        // Check if it's a file upload (form-data) or JSON
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

        
        if (strpos($contentType, 'multipart/form-data') !== false) {
            // Handle form-data (with file upload)
            $body = $_POST;
              
        } else {
              $body = json_decode(file_get_contents('php://input'), true);
              
               
            // Check if JSON parsing failed
            // if ($body === null && json_last_error() !== JSON_ERROR_NONE) {
            //     Response::error('Invalid JSON format: ' . json_last_error_msg(), [], 400);
            //     return;
            // }
        }
        // 2) Validate required fields
        if (empty($body['email']) || !filter_var($body['email'], FILTER_VALIDATE_EMAIL)) {
            Response::error('Valid email required', [$contentType], 422);
            return;
        }
        if (strlen($body['password'] ?? '') < 6) {
            Response::error('Password must be at least 6 chars', [], 422);
            return;
        }
        if (!$this->validateText($body['name'] ?? '', 1, 100)) {
            Response::error('Valid name required', [], 422);
            return;
        }

     
        $foodTypes = $this->foodTypeFormat($body['food_types'] ?? []);
         // 3) Sanitize or cast fields
        $data = [
            'email'      => $body['email'],
            'password'   => $body['password'],      // hashed in Model
            'name'       => $this->sanitizeText($body['name']),
            'phone'      => $body['phone'] ?? null,
            'address'    => $body['address'] ?? null,
            'food_types' => $foodTypes,   // Use processed food types
            'rating'     => $body['rating'] ?? 0,
          
        ];

        
        // 4) Insert into DB
            //The create model returns an ID of the vendor just created
        $vendorId = $this->vendorModel->create($data);
    
        //check if the key photo is not null and there are no errors
        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0){
            error_log("Image file found: " . $_FILES['image']['name']);
            error_log("Image tmp_name: " . $_FILES['image']['tmp_name']);
            
            $photoUrl = $this->cloudinaryService->uploadImage(
                $_FILES['image']['tmp_name'],  // Fixed
                "foodDelivery/vendors/vendor-{$vendorId}"
            );
            
            error_log("Cloudinary URL: " . ($photoUrl ?: 'FAILED'));
            
            if($photoUrl){
                $result = $this->vendorModel->imageUpdate($vendorId, ['image' => $photoUrl]);
                error_log("DB update result: " . ($result ? 'SUCCESS' : 'FAILED'));
            }
        }
        
        if (!$vendorId) {

            Response::error('Failed to create vendor', [], 500);
            return;
        } 

        Response::success('Vendor created', [], 201);
        return;
    }

    

    
    /**
     * P /api/admin/vendors/image/{id}
     * Update an existing vendor's details.
    */
    public function updateVendorImage(int $vendorId):void{
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        
        if (strpos($contentType, 'multipart/form-data') !== false) {
            $body = $_POST;
        } else {
            Response::error('Only accept form data', [], 400);
            return;
        }
        
        // Check if vendor exists
        $vendor = $this->vendorModel->find($vendorId);
        if (!$vendor) {
            Response::error('Vendor not found', [], 404);
            return;
        }
        
        // Check for image file
        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            //1. Upload the photo to cloudinary
             $photoUrl = $this->cloudinaryService->uploadImage(
                $_FILES['image']['tmp_name'], 
                "foodDelivery/vendors/vendor-{$vendorId}"
                //Path of the folders
            );

            //2. Update the vendor with the path of cloudinary file
            if($photoUrl){
                $result = $this->vendorModel->imageUpdate($vendorId, ['image' => $photoUrl]);
            }
        } else {
            Response::error("No valid image file provided", [], 400);
            return;
        }

        if(!$result){
            Response::error("Image upload failed", ["result" => $result], 400);
        }

        Response::success("Image upload success", [], 200);

    }
    /**
     * PUT /api/admin/vendors/{id}
     * Update an existing vendor's details.
    */
    public function update(int $id): void
    {  
        
        //1) Check if vendor exists
        $vendor = $this->vendorModel->find($id);
        if (!$vendor) {
            Response::error('Vendor not found', [], 404);
            return;
        }

        // 2) Parse the request body - handle both JSON and form-data
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        
        if (strpos($contentType, 'multipart/form-data') !== false) {
            $body = $_POST; // Form data
        } else {
            $body = json_decode(file_get_contents('php://input'), true); // JSON data
            
            // Check if JSON parsing failed
            if ($body === null && json_last_error() !== JSON_ERROR_NONE) {
                Response::error('Invalid JSON format: ' . json_last_error_msg(), [], 400);
                return;
            }
        }
        
        // Handle food_types array if it's a JSON string from form-data
        if (isset($body['food_types']) && is_string($body['food_types'])) {
            $body['food_types'] = json_decode($body['food_types'], true);
        }

        $updateData = [];
        if (isset($body['name'])) {
            if (!$this->validateText($body['name'], 1, 100)) {
                Response::error('Invalid name', [], 422);
                return;
            }
            $updateData['name'] = $this->sanitizeText($body['name']);
        }
        if (isset($body['address'])) {
            $updateData['address'] = $this->sanitizeText($body['address']);
        }
        if (isset($body['phone'])) {
            $updateData['phone'] = $body['phone'];
        }
        if (isset($body['food_types'])) {
            $updateData['food_types'] = $body['food_types']; // TEXT[] array
        }
        if (isset($body['rating'])) {
            $updateData['rating'] = (float) $body['rating'];
        }
        if (isset($body['image'])) {
            $updateData['image'] = $body['image'];
        }

         // If password is being updated
        if (isset($body['password']) && strlen($body['password']) >= 6) {
            $updateData['password'] = password_hash($body['password'], PASSWORD_DEFAULT);
        }

        // 4) Persist changes
        $result = $this->vendorModel->update($id, $updateData);
        if ($result) {
            Response::success('Vendor updated');
        } else {
            Response::error('Failed to update vendor', [], 500);
        }
        return;
    }

        /**
         * DELETE /api/admin/vendors/{id}
         * Delete a vendor. This should also cascade‐delete their foods (because of FK ON DELETE CASCADE).
        */
    public function delete(int $id): void
    {
        $vendor = $this->vendorModel->find($id);
        if (!$vendor) {
            Response::error('Vendor not found', [], 404);
            return;
        }

        $result = $this->vendorModel->delete($id);
        if ($result) {
            Response::success('Vendor deleted');
        } else {
            Response::error('Failed to delete vendor', [], 500);
        }

        return;
    }
    

}