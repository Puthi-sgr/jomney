<?php
namespace App\Controllers\Customer;

use App\Core\Response;
use App\Core\JWTService;
use App\Models\Customer;
use App\Traits\ValidationTrait;
use App\Core\CloudinaryService;
use App\Core\Request;
class CustomerAuthController{

    use ValidationTrait;
    private Customer $customerModel;
    private CloudinaryService $cloudinaryService;
    private Request $request;
   
    //Initialize connection to the admin
    public function __construct()
    {
        $this->request = new Request();
        $this->customerModel = new Customer();
        $this->cloudinaryService = new CloudinaryService();
        JWTService::init(); // ensure secrets are loaded
    }


      /**
     * POST /api/auth/register
     * { "email": "...", "password": "...", "name": "..." }
     */
    public function register(): Response
    {
        $body = $this->request->all();

        // Only require email, password, and name
        $email    = $body['email']    ?? null;
        $password = $body['password'] ?? null;
        $name     = $body['name']     ?? null;

        // Basic validation for required fields
        if (!$this->validationEmail($email)) {
            return Response::error('Valid email required', [], 422);
        }
        if (!$password || !$name) {
            return Response::error('Name and password are required', [], 422);
        }

        if ($this->customerModel->findByEmail($email)) {
            return Response::error('Email already exists', [], 409);
        }

        // Only pass required fields to create
        $customerId = $this->customerModel->create([
            'email'    => $email,
            'password' => $password,
            'name'     => $name
        ]);

        $token = JWTService::generateToken($customerId, 'customer');

        return Response::success('Registration successful', [
            'token'   => $token,
            'user_id' => $customerId
        ], 201);
    }
    /**
     * POST /api/auth/login
     * { "email": "...", "password": "..." }
     */
    public function login(): Response
    {
        $body = $this->request->all();
        $email = $body['email'] ?? '';
        $password = $body['password'] ?? '';

        // Debug: Log the incoming request (remove in production)
        error_log("Login attempt - Email: " . $email);
        error_log("Login attempt - Password length: " . strlen($password));

        // Basic validation
        if (!$this->validationEmail($email)) {
            return Response::error('Valid email required', [], 422);
        }

        if (strlen($password) < 6) {
            return Response::error('Password must be at least 6 characters', [], 422);
        }

        // Find user by email
        $user = $this->customerModel->findByEmail($email);
        
        // Debug: Check if user was found (remove in production)
        if (!$user) {
            error_log("User not found for email: " . $email);
            return Response::error('Invalid credentials email', [], 401);
        }

        // Debug: Log password verification (remove in production)
        $passwordMatch = password_verify($password, $user['password']);
        error_log("Password verification result: " . ($passwordMatch ? 'true' : 'false'));
        error_log("Stored hash: " . $user['password']);

        // Verify password
        if (!$passwordMatch) {
            error_log("Password mismatch for email: " . $email);
            return Response::error('Invalid credentials', [], 401);
        }

        // Generate token
        $token = JWTService::generateToken($user['id'], 'customer');

        // Success response
        return Response::success('Login successful', [
            'token' => $token,
            'user_id' => $user['id']
        ]);
    }

     /** POST /api/auth/logout */
    public function logout(): Response
    {
        // Token revocation / blacklist could live here
        return Response::success('Logged out successfully');
    }


    /** GET /api/auth/profile (auth:customer) */
    public function profile(): Response
    {
        $customerId = (int) ($_SERVER['user_id'] ?? 0);
        $user   = $this->customerModel->find($customerId);

        if (!$user) {
            return Response::error('Customer not found', [], 404);
        }
        unset($user['password']);
        return Response::success('Customer profile', $user);
    }

     /** PUT /api/auth/profile (auth:customer) */
    public function updateProfile(): Response
    {
        $customerId = (int) ($_SERVER['user_id'] ?? 0);
        $body   = $this->request->all();

        // Allow name / phone / address; ignore others
        $allowed = array_intersect_key($body, array_flip(['name', 'phone', 'address']));
        if (empty($allowed)) {
            return Response::error('Nothing to update', [], 422);
        }

        $updated = $this->customerModel->update($customerId, $allowed);
        return Response::success('Profile updated', ['Customer' => $updated], 201);
    }

    public function updateCustomerProfilePicture(): Response{
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        $customerId = $_SERVER['user_id'] ?? '';
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


    /** Post /api/auth/profile/image/{id} (auth:customer) */
}