<?php
namespace App\Controllers\Admin;

use App\Core\Response;
use App\Models\Customer;
use App\Core\CloudinaryService;
use App\Core\Request;

class AdminCustomerController
{
    private Customer $customerModel;
    private CloudinaryService $cloudinaryService;
    private Request $request;

    public function __construct()
    {
        $this->request = new Request();
        $this->customerModel = new Customer();
        $this->cloudinaryService = new CloudinaryService();
    }

    /**
     * GET /api/admin/customers
     * List all customers
     */
    public function index(): void
    {
        $customers = $this->customerModel->all();
        if (!$customers) {
            Response::error('No customers found', [], 404);
            return;
        }
        Response::success('Customers list', $customers);
    }

    /**
     * GET /api/admin/customers/{id}
     * View a single customer
     */
    public function show(int $customerId): void
    {
        $customer = $this->customerModel->find($customerId);
        if (!$customer) {
            Response::error('Customer not found', [], 404);
            return;
        }
        // Remove password
        unset($customer['password']);
        Response::success('Customer details', $customer);
        return;
    }
    
    /**
     * POST /api/admin/customers
     * Create a new customer
     */
    public function store(): void
    {
        $input = $this->request->all();
        
        // Validate required fields
        $requiredFields = ['email', 'password', 'name'];
        foreach ($requiredFields as $field) {
            if (empty($input[$field])) {
                Response::error("Field '{$field}' is required", [], 400);
                return;
            }
        }

        // Validate email format
        if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
            Response::error('Invalid email format', [], 400);
            return;
        }

        // Check if email already exists
        $existingCustomer = $this->customerModel->findByEmail($input['email']);
        if ($existingCustomer) {
            Response::error('Email already exists', [], 409);
            return;
        }

        // Prepare customer data
        $customerData = [
            'email' => $input['email'],
            'password' => $input['password'],
            'name' => $input['name'],
            'address' => $input['address'] ?? null,
            'phone' => $input['phone'] ?? null,
            'location' => $input['location'] ?? null,
            'lat_lng' => $input['lat_lng'] ?? null,
        ];

        $result = $this->customerModel->create($customerData);
        
        if ($result) {
            Response::success('Customer created successfully', [], 201);
        } else {
            Response::error('Failed to create customer', [], 500);
        }
    }

    /**
     * POST /api/admin/customers/image/{id}
     * Update a customer's image
     */
    public function updateCustomerImage(int $customerId): void
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        
        if (strpos($contentType, 'multipart/form-data') !== false) {
            $body = $_POST;
        } else {
            Response::error('Only accept form data', [], 400);
            return;
        }
        
        // Check if customer exists
        $customer = $this->customerModel->find($customerId);
        if (!$customer) {
            Response::error('Customer not found', [], 404);
            return;
        }
        
        // Check for image file
        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            error_log("Image file found: " . $_FILES['image']['name']);
            error_log("Image tmp_name: " . $_FILES['image']['tmp_name']);
            
            // 1. Upload the photo to cloudinary
            $photoUrl = $this->cloudinaryService->uploadImage(
                $_FILES['image']['tmp_name'], 
                "foodDelivery/customers/customer-{$customerId}"
                // Path of the folders
            );

            error_log("Cloudinary URL: " . ($photoUrl ?: 'FAILED'));

            // 2. Update the customer with the path of cloudinary file
            if ($photoUrl) {
                $result = $this->customerModel->imageUpdate($customerId, ['image' => $photoUrl]);
                error_log("DB update result: " . ($result ? 'SUCCESS' : 'FAILED'));
            } else {
                Response::error("Image upload to Cloudinary failed", [], 500);
                return;
            }
        } else {
            Response::error("No valid image file provided", [], 400);
            return;
        }

        if (!$result) {
            Response::error("Image upload failed", ["result" => $result], 500);
            return;
        }

        Response::success("Image upload success", [], 200);
    }

    /**
     * PUT /api/admin/customers/{id}
     * Update a customer
     */
    public function update(int $customerId): void
    {
        $customer = $this->customerModel->find($customerId);
        if (!$customer) {
            Response::error('Customer not found', [], 404);
            return;
        }

        $input = $this->request->all();
        
        // Validate email format if provided
        if (isset($input['email']) && !filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
            Response::error('Invalid email format', [], 400);
            return;
        }

        // Check if email already exists (excluding current customer)
        if (isset($input['email']) && $input['email'] !== $customer['email']) {
            $existingCustomer = $this->customerModel->findByEmail($input['email']);
            if ($existingCustomer) {
                Response::error('Email already exists', [], 409);
                return;
            }
        }

        // Prepare update data (exclude password from regular updates)
        $updateData = [];
        $allowedFields = ['email', 'name', 'address', 'phone', 'location', 'lat_lng'];
        
        foreach ($allowedFields as $field) {
            if (isset($input[$field])) {
                $updateData[$field] = $input[$field];
            }
        }

        if (empty($updateData)) {
            Response::error('No valid fields to update', [], 400);
            return;
        }

        $result = $this->customerModel->update($customerId, $updateData);
        
        if ($result) {
            Response::success('Customer updated successfully');
        } else {
            Response::error('Failed to update customer', [], 500);
        }
    }

      /**
     * DELETE /api/admin/customers/{id}
     * Delete a customer (cascade deletes orders, payment methods, etc.)
     */
    public function delete(int $customerId): void
    {
        $customer = $this->customerModel->find($customerId);
        if (!$customer) {
            Response::error('Customer not found', [], 404);
            return;
        }
        $result = $this->customerModel->delete($customerId);
        if (!$result) {
            Response::error('Failed to delete customer', [], 500);
            return;
        } 

        Response::success("Customer deleted", [], 200);
        return;
    }
}