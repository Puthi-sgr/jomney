<?php
namespace App\Controllers\Customer;

use App\Core\Response;
use App\Core\JWTService;
use App\Models\Customer;
use App\Traits\ValidationTrait;
use App\Core\CloudinaryService;
class CustomerAuthController{
    
    use ValidationTrait;
    private Customer $customerModel;
    private CloudinaryService $cloudinaryService;
   
    //Initialize connection to the admin
    public function __construct()
    {

        $this->customerModel = new Customer();
        $this->cloudinaryService = new CloudinaryService();
        JWTService::init(); // ensure secrets are loaded
    }


      /**
     * POST /api/auth/register
     * { "email": "...", "password": "...", "name": "..." }
     */
    public function register(): void
    {
        $body = json_decode(file_get_contents('php://input'), true) ?? [];

        // Only require email, password, and name
        $email    = $body['email']    ?? null;
        $password = $body['password'] ?? null;
        $name     = $body['name']     ?? null;

        // Basic validation for required fields
        if (!$this->validationEmail($email)) {
            Response::error('Valid email required', [], 422);
            return;
        }
        if (!$password || !$name) {
            Response::error('Name and password are required', [], 422);
            return;
        }

        if ($this->customerModel->findByEmail($email)) {
            Response::error('Email already exists', [], 409);
            return;
        }

        // Only pass required fields to create
        $customerId = $this->customerModel->create([
            'email'    => $email,
            'password' => $password,
            'name'     => $name
        ]);

        $token = JWTService::generateToken($customerId, 'customer');

        Response::success('Registration successful', [
            'token'   => $token,
            'user_id' => $customerId
        ], 201);
    }
    /**
     * POST /api/auth/login
     * { "email": "...", "password": "..." }
     */
    public function login(): void
    {
        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        $email = $body['email'] ?? '';
        $password = $body['password'] ?? '';

        // Debug: Log the incoming request (remove in production)
        error_log("Login attempt - Email: " . $email);
        error_log("Login attempt - Password length: " . strlen($password));

        // Basic validation
        if (!$this->validationEmail($email)) {
            Response::error('Valid email required', [], 422);
            return;
        }

        if (strlen($password) < 6) {
            Response::error('Password must be at least 6 characters', [], 422);
            return;
        }

        // Find user by email
        $user = $this->customerModel->findByEmail($email);
        
        // Debug: Check if user was found (remove in production)
        if (!$user) {
            error_log("User not found for email: " . $email);
            Response::error('Invalid credentials email', [], 401);
            return;
        }

        // Debug: Log password verification (remove in production)
        $passwordMatch = password_verify($password, $user['password']);
        error_log("Password verification result: " . ($passwordMatch ? 'true' : 'false'));
        error_log("Stored hash: " . $user['password']);

        // Verify password
        if (!$passwordMatch) {
            error_log("Password mismatch for email: " . $email);
            Response::error('Invalid credentials', [], 401);
            return;
        }

        // Generate token
        $token = JWTService::generateToken($user['id'], 'customer');

        // Success response
        Response::success('Login successful', [
            'token' => $token,
            'user_id' => $user['id']
        ]);
    }

     /** POST /api/auth/logout */
    public function logout(): void
    {
        // Token revocation / blacklist could live here
        Response::success('Logged out successfully');
    }


    /** GET /api/auth/profile (auth:customer) */
    public function profile(): void
    {
        $customerId = (int) ($_SERVER['user_id'] ?? 0);
        $user   = $this->customerModel->find($customerId);

        if (!$user) {
            Response::error('Customer not found', [], 404);
            return;
        }
        unset($user['password']);
        Response::success('Customer profile', $user);
    }

     /** PUT /api/auth/profile (auth:customer) */
    public function updateProfile(): void
    {
        $customerId = (int) ($_SERVER['user_id'] ?? 0);
        $body   = json_decode(file_get_contents('php://input'), true) ?? [];

        // Allow name / phone / address; ignore others
        $allowed = array_intersect_key($body, array_flip(['name', 'phone', 'address']));
        if (empty($allowed)) {
            Response::error('Nothing to update', [], 422);
            return;
        }

        $updated = $this->customerModel->update($customerId, $allowed);
        Response::success('Profile updated', ['Customer' => $updated], 201);
    }

    public function updateCustomerProfilePicture(): void{
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        $customerId = $_SERVER['user_id'] ?? '';
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
        
            
            // 1. Upload the photo to cloudinary
            $photoUrl = $this->cloudinaryService->uploadImage(
                $_FILES['image']['tmp_name'], 
                "foodDelivery/customers/customer-{$customerId}"
                // Path of the folders
            );

         
            // 2. Update the customer with the path of cloudinary file
            if ($photoUrl) {
                $result = $this->customerModel->imageUpdate($customerId, ['image' => $photoUrl]);
            
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


    /** Post /api/auth/profile/image/{id} (auth:customer) */
}