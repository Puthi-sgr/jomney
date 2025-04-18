
<?php
require __DIR__.'/../vendor/autoload.php';

use App\Controllers\MenuController;
use App\Core\Router;
use App\Core\ErrorHandler;

//.env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__, '/../');
$dotenv->load();
// Load environment variables
\App\Core\Config::load();

$router = new Router();
// Display PHP configuration information

// Define the routes
$router->get('/menu', [new MenuController(), 'index']);
$router->get('/menu/create', [new MenuController(), 'create']);
$router->post('/menu/store', [new MenuController(), 'store']);

// Basic routing example
$requestMethod = $_SERVER['REQUEST_METHOD'];
//provide the uri requested by the client
$request = $_SERVER['REQUEST_URI']; //tell the app how to handle the request

set_exception_handler([ErrorHandler::class, 'handleException']);

$router->dispatch($requestMethod, $request);