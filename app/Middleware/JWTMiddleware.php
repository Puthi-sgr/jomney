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

    public function __invoke(Request $req, callable $next): void
    {
        error_log("JWTMiddleware invoked");
        //This is the entry point for the middleware
        $this->req = $req; // Store the request for later use
        $this->handle($req, $next);
        //Call the next middleware or controller
      
    }
    
    public function handle(Request $req, callable $next) {
      
        //Get authorization
        $authHeader = $this->req->header('Authorization');
         
           
        if (!$authHeader) {
        
            $response = Response::error('Authorization header not found', [], 401);
            $response->json(); // Output JSON
            exit;
           
        }


        if (!preg_match('/Bearer\s(\S+)/', $authHeader, $matches)){
            
            $response = Response::error('Token not provided', [], 401);
            $response->json(); // Output JSON
            exit;
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

            if(!$userId || !$userRole) {
                $response = Response::error('Invalid token payload', [], 401);
                $response->json(); // Output JSON
                exit;// Stop further execution
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
               
                $response = Response::error("User not found", [], 404);
                $response->json(); // Output JSON
                exit;// Stop further execution
                
            }

            
            $_SERVER['user_id'] = $userId;
            $_SERVER['user_role'] = $userRole; 
            $next($req);
            
        }catch(Exception $e){
            $response = Response::error('Invalid token', ["stackTrace" => $e->getTrace()], 401);
            $response->json(); // Output JSON
            exit;// Stop further execution
        }
    }

}