<?php
namespace App\Controllers\Admin;

use App\Core\Response;
use App\Traits\ValidationTrait;
use App\Models\Vendor;

class AdminVendorController{

    use ValidationTrait;

    private Vendor $vendorModel;
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
    /**
         * POST /api/admin/vendors
         * Create a new vendor.
         * Body (multipart/form-data or JSON with a photo_url):
         *   { 
         * "email": "...", 
         * "password":"...", 
         * "name":"...", ... 
         * "food_types":["thai","burgers"], "photo_url":"https://..." }
     */
    public function store():void {
        //1) Parse the JSON body
        $body = json_decode(file_get_contents('php://input'), true);

        // 2) Validate required fields
        if (!filter_var($body['email'] ?? '', FILTER_VALIDATE_EMAIL)) {
            Response::error('Valid email required', [], 422);
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

         // 3) Sanitize or cast fields
        $data = [
            'email'      => $body['email'],
            'password'   => $body['password'],      // hashed in Model
            'name'       => $this->sanitizeText($body['name']),
            'phone'      => $body['phone'] ?? null,
            'address'    => $body['address'] ?? null,
            'food_types' => $body['food_types'] ?? [],   // TEXT[] array
            'rating'     => $body['rating'] ?? 0,
            'photo_url'  => $body['photo_url'] ?? null,   // URL from Cloudinary later
        ];

        // 4) Insert into DB
        $result = $this->vendorModel->create($data);
        if ($result) {
            Response::success('Vendor created', [], 201);
        } else {
            Response::error('Failed to create vendor', [], 500);     
        }
        return;
    }

    /**
     * PUT /api/admin/vendors/{id}
     * Update an existing vendor’s details.
    */
    public function update(int $id): void
    {  
        //1) Check if vendor exists
        $vendor = $this->vendorModel->find($id);
        if (!$vendor) {
            Response::error('Vendor not found', [], 404);
            return;
        }

        // 2) Parse the JSON body if vendor exists
        $body = json_decode(file_get_contents('php://input'), true);
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
        if (isset($body['photo_url'])) {
            $updateData['photo_url'] = $body['photo_url'];
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