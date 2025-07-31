<?php
namespace App\Controllers\Admin;

use App\Core\Response;
use App\Core\Request;
use App\Core\JWTService;
use App\Models\Admin;


class AdminAuthController{
    private Admin $adminModel;
    private Request $request;
    private Response $response;

    //Initialize connection to the admin
    public function __construct()
    {
        $this->request = new Request();
        $this->adminModel = new Admin();
        $this->response = new Response();
        JWTService::init(); // ensure secrets are loaded
    }

    

    /**
     * POST /api/admin/login
     * Request: { "email": "...", "password": "..." }
     * Response: { success, message, data: { token, admin_id } }
     */
    public function login(){
        
        if (!$this->request->isJson()) {
           return Response::error('Invalid content type, expected application/json', [], 400);
        }
 
        
        $email = $this->request->input('email', '');
        $password = $this->request->input('password', '');


         // 1) Find admin by email ** from the admin model**
        $admin = $this->adminModel->findByEmail($email);
        if (!$admin || !password_verify($password, $admin['password'])) {
           return Response::error('Invalid email or password', [], 401);
        }

        // 2) Generate a JWT with "sub" = admin_id and add a "role" claim
      
        $userId  = $admin['id'];               
        $role = 'admin';
        
        
        $token = JWTService::generateToken($userId, $role);

        // 3) Return token

        return Response::success('Login successful', ['token' => $token]);
         // 4) Set the token in the response header
        
    }

    public function logout(): Response
    {
        // In a real app, you might revoke the token or add to blacklist.
        return Response::success('Logged out successfully');
    }

    /**
     * GET /api/admin/user
     * Returns the currently authenticated admin’s profile.
     * JWT must include {"sub": admin_id, "role": "admin"}.
     */

     public function user(): Response
    {
        // JWTMiddleware (see next section) will have validated the token
        // and put the admin’s ID into $_SERVER['admin_id'].
        $adminId = (int) ($_SERVER['user_id'] ?? 0);
        $admin   = $this->adminModel->find($adminId);

        if (!$admin) {
            return Response::error('Admin not found in ctr', [], 404);
        }

        // Omit password field
        unset($admin['password']);
        return Response::success('Admin profile', $admin);
    }
}