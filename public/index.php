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
use App\Controllers\Customer\CustomerOrderController;
use App\Controllers\Customer\CustomerPaymentController;


// Public Controllers
use App\Controllers\Public\PublicController;

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

// ═══════════════════════════════════════════════════════════════════════════
// ─────── PUBLIC ENDPOINTS (No Authentication Required) ───────────────────────
// ═══════════════════════════════════════════════════════════════════════════
$publicCtrl = new PublicController();

// Main application initial data
$router->get('/api/public/vendors', [$publicCtrl, 'getAllVendors']);
$router->get('/api/public/foods', [$publicCtrl, 'getAllFoods']);

// Vendor details with food list
$router->get('/api/public/vendors/{id}', [$publicCtrl, 'getVendorDetails']);

// Individual food details
$router->get('/api/public/foods/{id}', [$publicCtrl, 'getFoodDetails']);


// ═══════════════════════════════════════════════════════════════════════════
// ─────── CUSTOMER ROUTES ──────────────────────────────────────────────────
// ═══════════════════════════════════════════════════════════════════════════

// ─────── Authentication & Profile Management ───────
$customerAuth = new CustomerAuthController();

// Public authentication routes
$router->post('/api/v1/auth/register', [$customerAuth, 'register']);
$router->post('/api/v1/auth/login', [$customerAuth, 'login']);

// Protected profile routes
$router->post('/api/v1/auth/logout', [$customerAuth, 'logout'], [CustomerMiddleware::class, 'check']);
$router->get('/api/v1/auth/profile', [$customerAuth, 'profile'], [CustomerMiddleware::class, 'check']);
$router->put('/api/v1/auth/profile', [$customerAuth, 'updateProfile'], [CustomerMiddleware::class, 'check']);
$router->post('/api/v1/auth/profile/image', [$customerAuth, 'updateCustomerProfilePicture'], [CustomerMiddleware::class, 'check']);

// ─────── Order Management ───────

$customerOrder = new CustomerOrderController();

$router->post('/api/v1/orders', [$customerOrder, 'store'], [CustomerMiddleware::class, 'check']);
$router->get('/api/v1/orders', [$customerOrder, 'index'], [CustomerMiddleware::class, 'check']);
$router->get('/api/v1/orders/{id}', [$customerOrder, 'show'], [CustomerMiddleware::class, 'check']);
$router->delete('/api/v1/orders/{id}', [$customerOrder, 'cancel'], [CustomerMiddleware::class, 'check']);


// ─────── Payment Management ───────

$customerPayment = new CustomerPaymentController();

// Payment Methods Management (Stripe Only)
$router->get('/api/v1/payment-methods', [$customerPayment, 'getPaymentMethods'], [CustomerMiddleware::class, 'check']);
// $router->post('/api/v1/payment-methods', [$customerPayment, 'addPaymentMethod'], [CustomerMiddleware::class, 'check']); // MOCK - COMMENTED OUT
// $router->delete('/api/v1/payment-methods/{id}', [$customerPayment, 'removePaymentMethod'], [CustomerMiddleware::class, 'check']); // MOCK - COMMENTED OUT

// Payment Processing (Stripe Only)
// $router->post('/api/v1/orders/{orderid}/payment', [$customerPayment, 'processPayment'], [CustomerMiddleware::class, 'check']); // MOCK - COMMENTED OUT


// Stripe Payment Methods Management
$router->post('/api/v1/payment-methods/stripe/setup-intent', [$customerPayment, 'createSetupIntent'], [CustomerMiddleware::class, 'check']);
$router->post('/api/v1/payment-methods/stripe/save', [$customerPayment, 'savePaymentMethod'], [CustomerMiddleware::class, 'check']);
$router->delete('/api/v1/payment-methods/stripe/{id}', [$customerPayment, 'removeStripePaymentMethod'], [CustomerMiddleware::class, 'check']);

$router->post('/api/v1/orders/{orderid}/stripe-payment', [$customerPayment, 'processStripePayment'], [CustomerMiddleware::class, 'check']);

// Legacy Payment Processing
// $router->post('/api/customer/payments/checkout', [$customerPayment, 'checkout'], [CustomerMiddleware::class, 'check']); // LEGACY - COMMENTED OUT

// Payment History
$router->get('/api/v1/payments', [$customerPayment, 'getPaymentHistory'], [CustomerMiddleware::class, 'check']);
$router->get('/api/v1/payments/{id}', [$customerPayment, 'getPayment'], [CustomerMiddleware::class, 'check']);

// ═══════════════════════════════════════════════════════════════════════════
// ─────── ADMIN ROUTES ────────────────────────────────────────────────────
// ═══════════════════════════════════════════════════════════════════════════

// ─────── Authentication & Profile ───────
$adminAuth = new AdminAuthController();
$router->post('/api/admin/login', [$adminAuth, 'login']);
$router->post('/api/admin/logout', [$adminAuth, 'logout'], null);
$router->get('/api/admin/user',    [$adminAuth, 'user'], [AdminMiddleware::class, 'check']);

// ─────── Dashboard & Statistics ───────
$statsCtrl = new AdminStatsController();
$router->get('/api/admin/stats', [$statsCtrl, 'index'], [AdminMiddleware::class, 'check']);

// ─────── Vendor Management ───────
$vendorCtrl = new AdminVendorController();
$router->get('/api/admin/vendors', [$vendorCtrl, 'index'], [AdminMiddleware::class, 'check']);
$router->post('/api/admin/vendors', [$vendorCtrl, 'store'], [AdminMiddleware::class, 'check']);
$router->get('/api/admin/vendors/{id}', [$vendorCtrl, 'show'], [AdminMiddleware::class, 'check']);
$router->post('/api/admin/vendors/{id}', [$vendorCtrl, 'updateVendorImage'], [AdminMiddleware::class, 'check']);
$router->put('/api/admin/vendors/{id}', [$vendorCtrl, 'update'], [AdminMiddleware::class, 'check']);
$router->delete('/api/admin/vendors/delete/{id}', [$vendorCtrl, 'delete'], [AdminMiddleware::class, 'check']);

// ─────── Food Management ───────
$foodCtrl = new AdminFoodController();
$router->get('/api/admin/foods', [$foodCtrl, 'index'], [AdminMiddleware::class, 'check']);
$router->post('/api/admin/foods', [$foodCtrl, 'store'], [AdminMiddleware::class, 'check']);
$router->post('/api/admin/foods/image/{id}', [$foodCtrl, 'updateFoodImage'], [AdminMiddleware::class, 'check']);
$router->get('/api/admin/foods/{id}', [$foodCtrl, 'show'], [AdminMiddleware::class, 'check']);
$router->put('/api/admin/foods/{id}', [$foodCtrl, 'update'], [AdminMiddleware::class, 'check']); 
$router->delete('/api/admin/foods/{id}', [$foodCtrl, 'delete'], [AdminMiddleware::class, 'check']);

// ─────── Inventory Management ───────
$router->get('/api/admin/foods/{id}/inventory', [$foodCtrl, 'getInventory'], [AdminMiddleware::class, 'check']);
$router->patch('/api/admin/foods/{id}/inventory/adjust', [$foodCtrl, 'adjustInventory'], [AdminMiddleware::class, 'check']);

// ─────── Order Management ───────
$orderCtrl = new AdminOrderController();
$router->get('/api/admin/orders', [$orderCtrl, 'index'], [AdminMiddleware::class, 'check']);
$router->get('/api/admin/orders/{orderId}', [$orderCtrl, 'show'], [AdminMiddleware::class, 'check']);
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

// ─────── System Settings ───────
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