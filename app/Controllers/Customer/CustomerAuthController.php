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
        $body = json_decode(file_get_contents('php://input'), true) ?? []; //json
        $email    = trim($body['email']    ?? '');
        $password = trim($body['password'] ?? '');
        $name     = trim($body['name']     ?? '');

        // Basic validation
        if (!$this->validationEmail($email)) {
            Response::error('Valid email required', [], 422);
            return;
        }
    
        if ($this->customerModel->findByEmail($email)) {
            Response::error('Email already exists', [], 409);
            return;
        }

        $hashed = password_hash($password, PASSWORD_BCRYPT);

        $customerId = $this->customerModel->create([
            'email'    => $email,
            'password' => $hashed,
            'name'     => $name
        ]);

        //Token is generated with the customer ID that we have just created
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
        $email    = trim($body['email']    ?? '');
        $password = trim($body['password'] ?? '');

        $user = $this->customerModel->findByEmail($email);

        //just check the email
        if (!$user) {
            Response::error('Invalid email address', [], 401);
            return;
        }

        //Checks the password after the user found
        if (!password_verify($password, $user['password'])) {
            Response::error('Incorrect password', [], 401);
            return;
        }


        $token = JWTService::generateToken($user['id'], 'customer');
        Response::success('Login successful', [
            'token'   => $token,
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

    public function updateCustomerProfilePicture(int $customerId): void{
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


    /** Post /api/auth/profile/image/{id} (auth:customer) */
}