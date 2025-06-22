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
use App\Middleware\AdminMiddleware;
use App\Middleware\CustomerMiddleware; 
use App\Middleware\JWTMiddleware;
use App\Middleware\CorsMiddleware;
use App\Models\Order;
use Firebase\JWT\JWT;

// Admin Controllers
use App\Controllers\Admin\AdminAuthController;
use App\Controllers\Admin\AdminStatsController;
use App\Controllers\Admin\AdminVendorController;
use App\Controllers\Admin\AdminFoodController;
use App\Controllers\Admin\AdminOrderController;
use App\Controllers\Admin\AdminCustomerController;
use App\Controllers\Admin\AdminPaymentController;
use App\Controllers\Admin\AdminSettingsController;

// Customer Controllers
use App\Controllers\Customer\CustomerAuthController;

CorsMiddleware::handle();

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


// ─────── Customer Authentication Routes ───────
$customerAuth = new CustomerAuthController();

// Public customer routes
$router->post('/api/v1/auth/register', [$customerAuth, 'register']);
$router->post('/api/v1/auth/login', [$customerAuth, 'login']);

// Protected customer routes
$router->post('/api/v1/auth/logout', [$customerAuth, 'logout'], [CustomerMiddleware::class, 'check']);
$router->get('/api/v1/auth/profile', [$customerAuth, 'profile'], [CustomerMiddleware::class, 'check']);
$router->put('/api/v1/auth/profile', [$customerAuth, 'updateProfile'], [CustomerMiddleware::class, 'check']);
$router->post('/api/v1/auth/profile/image', [$customerAuth, 'updateCustomerProfilePicture'], [CustomerMiddleware::class, 'check']);


// ─────── Admin Auth ───────
$adminAuth = new AdminAuthController();
$router->post('/api/admin/login',  [$adminAuth, 'login']);
$router->post('/api/admin/logout', [$adminAuth, 'logout'], null);
$router->get('/api/admin/user',    [$adminAuth, 'user'], [AdminMiddleware::class, 'check']);

// ─────── Dashboard Overview ───────
$statsCtrl = new AdminStatsController();
$router->get('/api/admin/stats', [$statsCtrl, 'index'], [AdminMiddleware::class, 'check']);

// ─────── Vendor CRUD ───────
$vendorCtrl = new AdminVendorController();
$router->get('/api/admin/vendors', [$vendorCtrl, 'index'], [AdminMiddleware::class, 'check']);
$router->post('/api/admin/vendors', [$vendorCtrl, 'store'], [AdminMiddleware::class, 'check']);
$router->get('/api/admin/vendors/{id}', [$vendorCtrl, 'show'], [AdminMiddleware::class, 'check']);
$router->post('/api/admin/vendors/{id}', [$vendorCtrl, 'updateVendorImage'], [AdminMiddleware::class, 'check']);
$router->put('/api/admin/vendors/{id}', [$vendorCtrl, 'update'], [AdminMiddleware::class, 'check']);
$router->delete('/api/admin/vendors//delete/{id}', [$vendorCtrl, 'delete'], [AdminMiddleware::class, 'check']);

// ─────── Food CRUD ───────
$foodCtrl = new AdminFoodController();
$router->get('/api/admin/foods', [$foodCtrl, 'index'], [AdminMiddleware::class, 'check']);
$router->post('/api/admin/foods', [$foodCtrl, 'store'], [AdminMiddleware::class, 'check']);
$router->post('/api/admin/foods/image/{id}', [$foodCtrl, 'updateFoodImage'], [AdminMiddleware::class, 'check']);
$router->get('/api/admin/foods/{id}', [$foodCtrl, 'show'], [AdminMiddleware::class, 'check']);
$router->put('/api/admin/foods/{id}', [$foodCtrl, 'update'], [AdminMiddleware::class, 'check']); 
$router->delete('/api/admin/foods/{id}', [$foodCtrl, 'delete'], [AdminMiddleware::class, 'check']);

// ─────── Order Management ───────
$orderCtrl = new AdminOrderController();
$router->get('/api/admin/orders', [$orderCtrl, 'index'], [AdminMiddleware::class, 'check']);
$router->get('/api/admin/orders/{id}', [$orderCtrl, 'show'], [AdminMiddleware::class, 'check']);
$router->patch('/api/admin/orders/{id}/status', [$orderCtrl, 'updateStatus'], [AdminMiddleware::class, 'check']);

// ─────── Customer Management ───────
$customerCtrl = new AdminCustomerController();
$router->get('/api/admin/customers', [$customerCtrl, 'index'], [AdminMiddleware::class, 'check']);
$router->post('/api/admin/customers', [$customerCtrl, 'store'], [AdminMiddleware::class, 'check']);
$router->get('/api/admin/customers/{id}', [$customerCtrl, 'show'], [AdminMiddleware::class, 'check']);
$router->put('/api/admin/customers/{id}', [$customerCtrl, 'update'], [AdminMiddleware::class, 'check']);
$router->post('/api/admin/customers/image/{id}', [$customerCtrl, 'updateCustomerImage'], [AdminMiddleware::class, 'check']);
$router->delete('/api/admin/customers/{id}', [$customerCtrl, 'delete'], [AdminMiddleware::class, 'check']);

// ─────── Payment Management ───────
$paymentCtrl = new AdminPaymentController();
$router->get('/api/admin/payments', [$paymentCtrl, 'index'], [AdminMiddleware::class, 'check']);
$router->get('/api/admin/payments/{id}', [$paymentCtrl, 'show'], [AdminMiddleware::class, 'check']);

// ─────── Settings (Order Statuses) ───────
$settingsCtrl = new AdminSettingsController();
$router->get('/api/admin/order-statuses', [$settingsCtrl, 'allStatuses'], [AdminMiddleware::class, 'check']);
$router->post('/api/admin/order-statuses', [$settingsCtrl, 'createStatus'], [AdminMiddleware::class, 'check']);
$router->get('/api/admin/order-statuses/{key}', [$settingsCtrl, 'getStatus'], [AdminMiddleware::class, 'check']);
$router->put('/api/admin/order-statuses/{key}', [$settingsCtrl, 'updateStatus'], [AdminMiddleware::class, 'check']);
$router->delete('/api/admin/order-statuses/{key}', [$settingsCtrl, 'deleteStatus'], [AdminMiddleware::class, 'check']);

/* ------------------AUTHs---------------------------------- */


/* ---------------------ORDERS---------------------------- */

/* ---------------------MENU---------------------------- */


/* ---------------------PAYMENTS---------------------------- */
$paymentController = new PaymentController();
//must be logged in to create payment
$router->post("/payments/create", [$paymentController, 'create'], [JWTMiddleware::class, 'check']);


//Webhook endpoint (Public, no auth)
$router->post('/payments/webhook', [$paymentController, 'webhook']);

// Basic routing example
$requestMethod = $_SERVER['REQUEST_METHOD'];
//provide the uri requested by the client
$request = $_SERVER['REQUEST_URI']; //tell the app how to handle the request

set_exception_handler([ErrorHandler::class, 'handleException']);

$router->dispatch($requestMethod, $request);