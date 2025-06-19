<?php
namespace App\Controllers\Admin;

use App\Core\Response;
use App\Traits\ValidationTrait;
use App\Models\Food;
use App\Models\Vendor; 
use App\Models\Inventory;
use App\Core\CloudinaryService;
use Cloudinary\Cloudinary;

class AdminFoodController{
    use ValidationTrait;

    private Food $foodModel;
    private Vendor $vendorModel;
    private Inventory $inventoryModel;
    private CloudinaryService $cloudinaryService;

    public function __construct()
    {
        $this->foodModel   = new Food();
        $this->vendorModel = new Vendor();
        $this->inventoryModel = new Inventory();
        $this->cloudinaryService = new CloudinaryService();
    }

    /**
     * GET /api/admin/foods
     * Return all food items, possibly filtered by vendor_id or category.
     * Example: /api/admin/foods?vendor_id=5
     */
    public function index(): void
    {
        // If you want to filter, look at $_GET parameters
        $filters = [];
        //checks if the "vendor_id" parameter exist within the url
        //http:://myWebSite.com/api/admin/foods?vendor_id=5
        if (isset($_GET['vendor_id']) && ctype_digit($_GET['vendor_id'])) {
            $filters['vendor_id'] = (int) $_GET['vendor_id'];
        }
        if (isset($_GET['category'])) {
            $filters['category'] = $_GET['category'];
        }

        //filter might have
        //filter = [
        //    'vendor_id' => 5,
        //    'category' => 'thai'
        //]
        $foods = $this->foodModel->all($filters);
        Response::success('Foods list', $foods);
        return;
    }
    /**
     * GET /api/admin/foods/{id}
    */
    public function show(int $id): void
    {
        $food = $this->foodModel->find($id);
        if (!$food) {
            Response::error('Food item not found', [], 404);
            return;
        }
        Response::success('Food details',[$food], 200);
    }

    /**
     * POST /api/admin/foods
     * Body: { 
     * "vendor_id":5, 
     * "name":"Burger", 
     * "description":"...", 
     * "category":"fast_food", 
     * "price":9.99, 
     * "ready_time":15, 
     * "images":["url1","url2"] 
     * }
    */
    public function store(): void
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        
        if (strpos($contentType, 'multipart/form-data') !== false) {
            $body = $_POST;
        } else {
            $body = json_decode(file_get_contents('php://input'), true);
        }

        // DEBUG: Log the $_FILES structure
        error_log("FILES structure: " . print_r($_FILES, true));

        // 1) Validate vendor_id exists
        $vendorId = (int) ($body['vendor_id'] ?? 0);
        if (!$this->vendorModel->find($vendorId)) {
            Response::error('Vendor not found',[], 422);
            return;
        }

        // 2) Validate name, price
        if (!$this->validateText($body['name'] ?? '', 1, 100)) {
            Response::error('Invalid name', [] , 422);
            return;
        }
        if (!isset($body['price']) || !is_numeric($body['price'])) {
            Response::error('Valid price required', [], 422);
            return;
        }

        // 3) Optional fields
        $description = $body['description'] ?? null;
        $category    = $body['category'] ?? null;
        $readyTime   = isset($body['ready_time']) ? (int) $body['ready_time'] : null;

        // 4) Build data array
        $data = [
            'vendor_id'   => $vendorId,
            'name'        => $this->sanitizeText($body['name']),
            'description' => $description ? $this->sanitizeText($description) : null,
            'category'    => $category,
            'price'       => (float) $body['price'],
            'ready_time'  => $readyTime,
            'rating'      => $body['rating'] ?? 0.0,
            'image'       => null, // Single image
        ];

        // 5) Insert the data into DB
        $foodId = $this->foodModel->create($data);
        
        if (!$foodId) {
            Response::error('Failed to create food', [], 500);
            return;
        }

        // 6) Seed the inventory
        $this->inventoryModel->create($foodId, (int)$body ?? 1);


        // 7) Handle single image upload
        $uploadedImageUrl = null;
        
        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            $uploadedImageUrl = $this->cloudinaryService->uploadImage(
                $_FILES['image']['tmp_name'],
                "foodDelivery/vendors/vendor-{$vendorId}/food-{$foodId}/image"
            );
            
            if ($uploadedImageUrl) {
                $this->foodModel->imageUpdate($foodId, $uploadedImageUrl);
                error_log("Uploaded image: " . $uploadedImageUrl);
            }
        }

        Response::success('Food created successfully', [
            'food_id' => $foodId, 
            'image_uploaded' => $uploadedImageUrl ? true : false,
            'image_url' => $uploadedImageUrl
        ], 201);
    }

    private function processImagesArray($filesArray, $vendorId, $foodId): array
    {
        $uploadedImages = [];
        
        if (is_array($filesArray['name'])) {
            // Multiple files uploaded with array structure
            error_log("Processing multiple files as array");
            $fileCount = count($filesArray['name']);
            
            for ($i = 0; $i < $fileCount; $i++) {
                if ($filesArray['error'][$i] === 0) {
                    $photoUrl = $this->cloudinaryService->uploadImage(
                        $filesArray['tmp_name'][$i],
                        "foodDelivery/vendors/vendor-{$vendorId}/food-{$foodId}/image-{$i}"
                    );
                    
                    if ($photoUrl) {
                        $uploadedImages[] = $photoUrl;
                        error_log("Uploaded image " . ($i + 1) . ": " . $photoUrl);
                    }
                }
            }
        } else {
            // Single file uploaded
            error_log("Processing single file");
            if ($filesArray['error'] === 0) {
                $photoUrl = $this->cloudinaryService->uploadImage(
                    $filesArray['tmp_name'],
                    "foodDelivery/vendors/vendor-{$vendorId}/food-{$foodId}/image-0"
                );
                
                if ($photoUrl) {
                    $uploadedImages[] = $photoUrl;
                    error_log("Uploaded single image: " . $photoUrl);
                }
            }
        }
        
        return $uploadedImages;
    }

    private function processIndividualImages($files, $vendorId, $foodId): array
    {
        $uploadedImages = [];
        $imageIndex = 0;
        
        // Look for any key that starts with 'image'
        foreach ($files as $key => $file) {
            if (strpos($key, 'image') === 0 && $file['error'] === 0) {
                error_log("Processing individual file: " . $key);
                $photoUrl = $this->cloudinaryService->uploadImage(
                    $file['tmp_name'],
                    "foodDelivery/vendors/vendor-{$vendorId}/food-{$foodId}/image-{$imageIndex}"
                );
                
                if ($photoUrl) {
                    $uploadedImages[] = $photoUrl;
                    error_log("Uploaded image from {$key}: " . $photoUrl);
                    $imageIndex++;
                }
            }
        }
        
        return $uploadedImages;
    }
     public function updateVendorImage(int $foodId): void {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        
        if (strpos($contentType, 'multipart/form-data') !== false) {
            $body = $_POST;
        } else {
            Response::error('Only accept form data', [], 400);
            return;
        }
        
        // Check if food exists
        $food = $this->foodModel->find($foodId);
        if (!$food) {
            Response::error('Food not found', [], 404);
            return;
        }
        
        $vendorId = $food["vendor_id"];
        
        // Handle single image upload (consistent with store method)
        $uploadedImageUrl = null;
        
        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            // Upload the photo to cloudinary with consistent naming
            $uploadedImageUrl = $this->cloudinaryService->uploadImage(
                $_FILES['image']['tmp_name'], 
                "foodDelivery/vendors/vendor-{$vendorId}/food-{$foodId}/image"
            );

            // Update the food with the new image URL
            if ($uploadedImageUrl) {
                $result = $this->foodModel->imageUpdate($foodId, $uploadedImageUrl);
            
                if (!$result) {
                    Response::error("Image upload failed", ["result" => $result], 500);
                    return;
                }
            
                error_log("Updated image for food {$foodId}: " . $uploadedImageUrl);
                Response::success("Image updated successfully", [
                    'food_id' => $foodId,
                    'image_url' => $uploadedImageUrl
                ], 200);
            } else {
                Response::error("Failed to upload image to Cloudinary", [], 500);
            }
        } else {
            Response::error("No valid image file provided", [], 400);
        }
    }
    

    /**
     * PUT /api/admin/foods/{id}
     * Body: same as store() except vendor_id is optional if not changed.
    */
   public function update(int $foodId): void{

        $food = $this->foodModel->find($foodId);
        if (!$food) {
            Response::error('Food item not found', [], 404);
            return;
        }

        $body = json_decode(file_get_contents('php://input'), true);
        $updateData = [];

        // If vendor is updated
        if (isset($body['vendor_id'])) {
               
            $vendorId = (int) $body['vendor_id'];
            if (!$this->vendorModel->find($vendorId)) {
                Response::error('Vendor not found', [], 422);
                return;
            }
            $updateData['vendor_id'] = $vendorId;
        }else if(!isset($body['vendor_id'])){
            Response::error('Vendor id key is not set', [], 422);
            return;
        }

        // If name is updated
        if (isset($body['name'])) {
            if (!$this->validateText($body['name'], 1, 100)) {
               Response::error('Invalid name', [], 422);
               return;
            }
            $updateData['name'] = $this->sanitizeText($body['name']);
        }

        // Price
        if (isset($body['price'])) {
            if (!is_numeric($body['price']) || $body['price'] < 0) {
                    Response::error('Invalid price', [], 422);
                    return;
            }
            $updateData['price'] = (float) $body['price'];
        }

        // Description, category, ready_time, rating, images
        if (array_key_exists('description', $body)) {
            $updateData['description'] = $body['description'] 
                ? $this->sanitizeText($body['description']) 
                : null;
        }
         if (array_key_exists('category', $body)) {
            $updateData['category'] = $body['category'] ?? null;
        }
        if (array_key_exists('ready_time', $body)) {
            $updateData['ready_time'] = is_numeric($body['ready_time']) 
                ? (int) $body['ready_time'] 
                : null;
        }
        if (array_key_exists('rating', $body)) {
            $updateData['rating'] = is_numeric($body['rating']) 
                ? (float) $body['rating'] 
                : 0.0;
        }
        if (array_key_exists('images', $body)) {
            $updateData['images'] = $body['images'] ?? [];
        }

         // 5) Persist changes
        $result = $this->foodModel->update($foodId, $updateData);
        if ($result) {
            Response::success('Food item updated');
            return;
        } else {
            Response::error('Failed to update food item',[], 500);
            return;
        }

   }

   /**
     * DELETE /api/admin/foods/{id}
     */
    public function delete(int $foodId): void
    {
        $food = $this->foodModel->find($foodId);
        if (!$food) {
            Response::error('Food item not found', [], 404);
            return;
        }

        $result = $this->foodModel->delete($foodId);
        
        if(!$result) {
            Response::error('Failed to delete food item', [], 500);
            return;
        }
        
        Response::success('Food item deleted');
        return;
    }
}
