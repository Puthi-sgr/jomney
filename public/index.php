<?php
require __DIR__.'/../vendor/autoload.php';

use App\Controllers\AuthController;
use App\Controllers\MenuController;
use App\Controllers\OrderController;
use App\Core\Router;
use App\Core\ErrorHandler;
use App\Core\JWTService;
use App\Middleware\AuthMiddleware;
use App\Middleware\JWTMiddleware;
use App\Models\Order;

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

$router->post('/loginJWT', [$authController, 'login'], null);

$router->get('/logout', [$authController, 'logout'], [JWTMiddleware::class, 'check'] );

$orderController = new OrderController();
$router->get('/orders', [$orderController, 'index'], [JWTMiddleware::class, 'check']);

$router->post('/orders', [$orderController, 'create'], [JWTMiddleware::class, 'check']);
// Basic routing example
$requestMethod = $_SERVER['REQUEST_METHOD'];
//provide the uri requested by the client
$request = $_SERVER['REQUEST_URI']; //tell the app how to handle the request

set_exception_handler([ErrorHandler::class, 'handleException']);

$router->dispatch($requestMethod, $request);