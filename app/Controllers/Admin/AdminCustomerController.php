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
    public function index(): Response
    {
        $customers = $this->customerModel->all();
        if (!$customers) {
            return Response::error('No customers found', [], 404);
        }
        return Response::success('Customers list', $customers);
    }

    /**
     * GET /api/admin/customers/{id}
     * View a single customer
     */
    public function show(int $customerId): Response
    {
        $customer = $this->customerModel->find($customerId);
        if (!$customer) {
            return Response::error('Customer not found', [], 404);
        }
        // Remove password
        unset($customer['password']);
        return Response::success('Customer details', $customer);
    }
    
    /**
     * POST /api/admin/customers
     * Create a new customer
     */
    public function store(): Response
    {
        $input = $this->request->all();
        
        // Validate required fields
        $requiredFields = ['email', 'password', 'name'];
        foreach ($requiredFields as $field) {
            if (empty($input[$field])) {
                return Response::error("Field '{$field}' is required", [], 400);
            }
        }

        // Validate email format
        if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
            return Response::error('Invalid email format', [], 400);
        }

        // Check if email already exists
        $existingCustomer = $this->customerModel->findByEmail($input['email']);
        if ($existingCustomer) {
            return Response::error('Email already exists', [], 409);
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
            return Response::success('Customer created successfully', [], 201);
        } else {
            return Response::error('Failed to create customer', [], 500);
        }
    }

    /**
     * POST /api/admin/customers/image/{id}
     * Update a customer's image
     */
    public function updateCustomerImage(int $customerId): Response
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        
        if (strpos($contentType, 'multipart/form-data') !== false) {
            $body = $_POST;
        } else {
            return Response::error('Only accept form data', [], 400);
        }
        
        // Check if customer exists
        $customer = $this->customerModel->find($customerId);
        if (!$customer) {
            return Response::error('Customer not found', [], 404);
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
                return Response::error("Image upload to Cloudinary failed", [], 500);
            }
        } else {
            return Response::error("No valid image file provided", [], 400);
        }

        if (!$result) {
            return Response::error("Image upload failed", ["result" => $result], 500);
        }

        return Response::success("Image upload success", [], 200);
    }

    /**
     * PUT /api/admin/customers/{id}
     * Update a customer
     */
    public function update(int $customerId): Response
    {
        $customer = $this->customerModel->find($customerId);
        if (!$customer) {
            return Response::error('Customer not found', [], 404);
        }

        $input = $this->request->all();
        
        // Validate email format if provided
        if (isset($input['email']) && !filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
            return Response::error('Invalid email format', [], 400);
        }

        // Check if email already exists (excluding current customer)
        if (isset($input['email']) && $input['email'] !== $customer['email']) {
            $existingCustomer = $this->customerModel->findByEmail($input['email']);
            if ($existingCustomer) {
                return Response::error('Email already exists', [], 409);
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
            return Response::error('No valid fields to update', [], 400);
        }

        $result = $this->customerModel->update($customerId, $updateData);

        if ($result) {
            return Response::success('Customer updated successfully');
        } else {
            return Response::error('Failed to update customer', [], 500);
        }
    }

      /**
     * DELETE /api/admin/customers/{id}
     * Delete a customer (cascade deletes orders, payment methods, etc.)
     */
    public function delete(int $customerId): Response
    {
        $customer = $this->customerModel->find($customerId);
        if (!$customer) {
            return Response::error('Customer not found', [], 404);
        }
        $result = $this->customerModel->delete($customerId);
        if (!$result) {
            return Response::error('Failed to delete customer', [], 500);
        }

        return Response::success("Customer deleted", [], 200);
    }
}