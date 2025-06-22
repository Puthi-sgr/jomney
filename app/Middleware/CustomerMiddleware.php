<?php
namespace App\Middleware;

use App\Core\Response;
use App\Core\JWTService;
use App\Models\Customer;

use Exception;

class CustomerMiddleware{
    /**
     * Call this at the top of any admin route to ensure:
     *  - A valid JWT is provided
     *  - The token’s "role" is "admin"
     *  - The "sub" exists in the "admin" table
    */
    public static function check():void {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? 
                  $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? 
                  getallheaders()['Authorization'] ?? 
                  getallheaders()['authorization'] ?? '';

        if (!preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            Response::error('Token not provided', [],  401);
        }      
        
        $token = $matches[1];

        try{
            // 2) Validate and decode
            $decoded = JWTService::validateToken($token);
             if (!isset($decoded->role) || $decoded->role !== 'customer') {
                Response::error('Forbidden: Admins only',[],  403);
                return;
            }

             // 4) Check that this customer actually exists in DB
            $customerId    = (int) $decoded->sub;
            $customerModel = new Customer();
            $customer      = $customerModel->find($customerId);

            if(!$customer){
                Response::error('Customer not found',[],  404);
                return;
            }

            // 5) All good: store admin_id in $_SERVER for controllers to use
            $_SERVER['user_id'] = $customerId;
            $_SERVER['user_role'] = $payload->role ?? 'customer';

        }catch(Exception $e){
            // invalid or expired token
            Response::error('Invalid or expired token', [],401);
        }
    }
     
    

}