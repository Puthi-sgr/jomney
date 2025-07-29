<?php


namespace App\Middleware;

use App\Core\JWTService;
use App\Models\Admin;
use App\Models\Customer;
use App\Core\Response;
use App\Core\Request;
use Exception;

class JWTMiddleware{

    private Request $request;

    //The request is no longer instantiated, instead inject through param
    public function __construct(Request $request)
    {
        $this->request = $request;
    }
    public function check():void {
        //Get authorization
        $authHeader = $this->request->header('Authorization');

        if (!$authHeader) {
            Response::error('Authorization header not found', [], 401);
            return;
        }


        if (!preg_match('/Bearer\s(\S+)/', $authHeader, $matches)){
            Response::error('Token not provided', [], 401);
            return;
        }

        $token = $matches[1];

        // Debug: Log the token we extracted
        error_log("Extracted token: " . $token);
        error_log("Token length: " . strlen($token));

        try{
            //2. Validate the token
            $payload = JWTService::validateToken($token);

            error_log("Logging in as a: ". $payload->role ?? 'unknown');
        
            //3.Attach user ID
            $userId = (int) $payload->sub;
            $userRole = $payload->role; // ← Add this line!

            if(!$userId || !$userRole) {
                Response::error('Invalid token payload', [], 401);
                return;
            }

            //4. Check if they exists

            if($userRole === 'admin') {
                $adminModel = new Admin();
                $user = $adminModel->find($userId);
            } else if($userRole === 'customer') {
                $customerModel = new Customer();
                $user = $customerModel->find($userId);
            }

            //5. If user exists, store in $_SERVER
            if(!$user){
                Response::error("User not found", [], 404);
                return;
            }

            $_SERVER['user_id'] = $userId;
            $_SERVER['user_role'] = $userRole; 
            
           //Adding these checking logic adds overhead to performance
            if($userRole === 'admin') {
                AdminMiddleware::check();
            } else if($userRole === 'customer') {
                CustomerMiddleware::check();
            } else {
                Response::error('Forbidden: Invalid user role', [], 403);
                return;
            }
        }catch(Exception $e){
            Response::error('Invalid token', ["stackTrace" => $e->getTrace()], 401);
        }
    }

}