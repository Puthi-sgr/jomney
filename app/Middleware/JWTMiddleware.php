<?php


namespace App\Middleware;

use App\Core\JWTService;
use App\Models\Admin;
use App\Models\Customer;
use App\Core\Response;
use App\Core\Request;
use Exception;

class JWTMiddleware{


    //The req is no longer instantiated, instead inject through param
    public function __construct(Request $req)
    {
        $this->req = $req;
    }

    public function __invoke(Request $req, callable $next): Response
    {
        error_log("JWTMiddleware invoked");
        //This is the entry point for the middleware
        $this->req = $req; // Store the request for later use
        return $this->handle($req, $next);
        //Call the next middleware or controller

    }
    
    public function handle(Request $req, callable $next):Response {
      
        //Get authorization
        $authHeader = $this->req->header('Authorization');
         
           
    
        if (!$authHeader) {
            return Response::error('Authorization header not found', [], 401);
        }

        if (!preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            return Response::error('Token not provided', [], 401);
        }

        $token = $matches[1];

        error_log("Token: $token");
        

        try{
            //2. Validate the token
            $payload = JWTService::validateToken($token);

            error_log("Logging in as a: ". $payload->role ?? 'unknown');
        
            //3.Attach user ID
            $userId = (int) $payload->sub;
            $userRole = $payload->role; // ← Add this line!

            if (!$userId || !$userRole) {
                return Response::error('Invalid token payload', [], 401);
            }

            //4. Check if they exists

            if ($userRole === 'admin') {
                $adminModel = new Admin();
                $user = $adminModel->find($userId);
            } else if ($userRole === 'customer') {
                $customerModel = new Customer();
                $user = $customerModel->find($userId);
            }

            //5. If user exists, store in $_SERVER
            if (!$user) {
                return Response::error("User not found", [], 404);
            }

            
            $_SERVER['user_id'] = $userId;
            $_SERVER['user_role'] = $userRole; 
            
            
            return $next($req) ;

        
        
            
        }catch(Exception $e){
            $response = Response::error('Invalid token', ["stackTrace" => $e->getTrace()], 401);
            return $response;
        }
    }

}