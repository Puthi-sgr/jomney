<?php
require __DIR__.'/../vendor/autoload.php';

use App\Controllers\AuthController;
use App\Controllers\MenuController;
use App\Controllers\OrderController;
use App\Controllers\PaymentController;
use App\Core\Router;
use App\Core\ErrorHandler;
use App\Core\JWTService;
use App\Middleware\AuthMiddleware;
use App\Middleware\JWTMiddleware;
use App\Models\Order;
use Firebase\JWT\JWT;

//.env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__, '/../');
$dotenv->load();
// Load environment variables
\App\Core\Config::load();

$router = new Router();
// Display PHP configuration information

// Define the route
$router->get('/', function (){
        echo "This is a public's homepage";
    });

$router->get('/menu', [new MenuController(), 'index'], [AuthMiddleware::class, 'check']);
$router->get('/menu/create', [new MenuController(), 'create']);
$router->post('/menu/store', [new MenuController(), 'store']);

$authController = new AuthController();
// $router->post('/register',[$authController, 'register'], [JWTMiddleware::check()] );

/* ------------------AUTHs---------------------------------- */
$router->post('/register', [$authController, 'register'], null);
$router->post('/loginJWT', [$authController, 'login'], null);
$router->get('/logout', [$authController, 'logout'], [JWTMiddleware::class, 'check'] );

/* ---------------------ORDERS---------------------------- */
$orderController = new OrderController();
$router->get('/orders', [$orderController, 'index'], [JWTMiddleware::class, 'check']);
$router->post('/orders', [$orderController, 'create'], [JWTMiddleware::class, 'check']);

/* ---------------------MENU---------------------------- */
$menuController = new MenuController();
$router->get('/menu-items', [$menuController, 'index'], [JWTMiddleware::class, 'check']);
$router->post('/menu-items', [$menuController, 'create'], [JWTMiddleware::class, 'check']);


/* ---------------------PAYMENTS---------------------------- */
$paymentController = new PaymentController();
//must be logged in to create payment
$router->post("/payments/create", [$paymentController, 'create'], [JWTMiddleware::class, 'check']);

$router->get('/test-jwt', function() {
    echo "=== JWT Debug Test v6 Format ===\n\n";
    
    $token = JWTService::generateToken(123);
    echo "Generated token: " . $token . "\n\n";
    
    try {
        // v6 alternative syntax - array of keys
        $keys = [
            'HS256' => new \Firebase\JWT\Key($_ENV['JWT_SECRET'], 'HS256')
        ];
        
        $decoded = \Firebase\JWT\JWT::decode($token, $keys);
        echo "Decode successful with array format!\n";
        echo "Decoded payload: " . json_encode($decoded) . "\n";
        
    } catch (Exception $e) {
        echo "Array format failed: " . $e->getMessage() . "\n";
        
        // Try the old v5 syntax as fallback
        try {
            echo "Trying v5 compatibility mode...\n";
            $decoded = \Firebase\JWT\JWT::decode($token, $_ENV['JWT_SECRET'], ['HS256']);
            echo "v5 syntax worked!\n";
            echo "Decoded payload: " . json_encode($decoded) . "\n";
        } catch (Exception $e2) {
            echo "v5 syntax also failed: " . $e2->getMessage() . "\n";
        }
    }
});
//Webhook endpoint (Public, no auth)
$router->post('/payments/webhook', [$paymentController, 'webhook']);

// Basic routing example
$requestMethod = $_SERVER['REQUEST_METHOD'];
//provide the uri requested by the client
$request = $_SERVER['REQUEST_URI']; //tell the app how to handle the request

set_exception_handler([ErrorHandler::class, 'handleException']);

$router->dispatch($requestMethod, $request);