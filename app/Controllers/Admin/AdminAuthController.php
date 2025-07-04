<?php
namespace App\Controllers\Admin;

use App\Core\Response;
use App\Core\JWTService;
use App\Models\Admin;

class AdminAuthController{
    private Admin $adminModel;

    //Initialize connection to the admin
    public function __construct()
    {
        $this->adminModel = new Admin();
        JWTService::init(); // ensure secrets are loaded
    }

    /**
     * POST /api/admin/login
     * Request: { "email": "...", "password": "..." }
     * Response: { success, message, data: { token, admin_id } }
     */
    public function login(): void{
        $rawInput = file_get_contents('php://input');
 
        
        $body = json_decode($rawInput, true);
        $email = $body['email'] ?? '';
        $password = $body['password'] ?? '';

         // 1) Find admin by email ** from the admin model**
        $admin = $this->adminModel->findByEmail($email);
        if (!$admin || !password_verify($password, $admin['password'])) {
            Response::error('Invalid credentials', [], 401);
            return;
        }

        // 2) Generate a JWT with "sub" = admin_id and add a "role" claim
      
        $userId  = $admin['id'];               
        $role = 'admin';
        
        
        $token = JWTService::generateToken($userId, $role);

        // 3) Return token

        Response::success('Login successful', ['token' => $token, 'admin_id' => $userId]);
        return;
    }

    public function logout(): void
    {
        // In a real app, you might revoke the token or add to blacklist.
        Response::success('Logged out successfully');
    }

    /**
     * GET /api/admin/user
     * Returns the currently authenticated admin’s profile.
     * JWT must include {"sub": admin_id, "role": "admin"}.
     */

     public function user(): void
    {
        // JWTMiddleware (see next section) will have validated the token
        // and put the admin’s ID into $_SERVER['admin_id'].
        $adminId = (int) ($_SERVER['admin_id'] ?? 0);
        $admin   = $this->adminModel->find($adminId);

        if (!$admin) {
            Response::error('Admin not found', [], 404);
            return;
        }

        // Omit password field
        unset($admin['password']);
        Response::success('Admin profile', $admin);
        return;
    }
}