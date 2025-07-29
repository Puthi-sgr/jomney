<?php
require __DIR__.'/../vendor/autoload.php';

use App\Core\Request;
use App\Controllers\AuthController;
use App\Controllers\MenuController;
use App\Controllers\OrderController;
use App\Controllers\PaymentController;
use App\Core\Router;
use App\Core\ErrorHandler;
use App\Core\JWTService;
use App\Middleware\JWTMiddleware;
use App\Middleware\CorsMiddleware;
use App\Models\Order;

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
use App\Controllers\TestController;

CorsMiddleware::handle();

//.env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__, '/../');
$dotenv->load();
// Load environment variables
\App\Core\Config::load();

$request = new Request(); 

$router = new Router($request);
// Display PHP configuration information

// Define the route
$router->get('/', function (){
        echo "This is a public's homepage";
    });

// ═══════════════════════════════════════════════════════════════════════════
// ─────── TESTING ROUTES (Development Only) ────────────────────────────────
// ═══════════════════════════════════════════════════════════════════════════

$testController = new TestController();
$router->get('/test/stripe-payment-method', [$testController, 'stripePaymentMethodTest']);

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
$router->post('/api/v1/auth/logout', [$customerAuth, 'logout'], [new JWTMiddleware($request), 'check']);
$router->get('/api/v1/auth/profile', [$customerAuth, 'profile'], [new JWTMiddleware($request), 'check']);
$router->put('/api/v1/auth/profile', [$customerAuth, 'updateProfile'], [new JWTMiddleware($request), 'check']);
$router->post('/api/v1/auth/profile/image', [$customerAuth, 'updateCustomerProfilePicture'], [new JWTMiddleware($request), 'check']);

// ─────── Order Management ───────

$customerOrder = new CustomerOrderController();

$router->post('/api/v1/orders', [$customerOrder, 'store'], [new JWTMiddleware($request), 'check']);
$router->get('/api/v1/orders', [$customerOrder, 'index'], [new JWTMiddleware($request), 'check']);
$router->get('/api/v1/orders/{id}', [$customerOrder, 'show'], [new JWTMiddleware($request), 'check']);
$router->delete('/api/v1/orders/{id}', [$customerOrder, 'cancel'], [new JWTMiddleware($request), 'check']);

// ─────── Payment Management ───────

$customerPayment = new CustomerPaymentController();

// Payment Methods Management (Stripe Only)
$router->get('/api/v1/payment-methods', [$customerPayment, 'getPaymentMethods'], [new JWTMiddleware($request), 'check']);
$router->post('/api/v1/payment-methods/stripe/setup-intent', [$customerPayment, 'createSetupIntent'], [new JWTMiddleware($request), 'check']);
$router->post('/api/v1/payment-methods/stripe/save', [$customerPayment, 'savePaymentMethod'], [new JWTMiddleware($request), 'check']);
$router->delete('/api/v1/payment-methods/stripe/{id}', [$customerPayment, 'removeStripePaymentMethod'], [new JWTMiddleware($request), 'check']);

//stripe payment processing
$router->post('/api/v1/orders/{orderid}/stripe-payment', [$customerPayment, 'processStripePayment'], [new JWTMiddleware($request), 'check']);
// Payment History
$router->get('/api/v1/payments', [$customerPayment, 'getPaymentHistory'], [new JWTMiddleware($request), 'check']);
$router->get('/api/v1/payments/{id}', [$customerPayment, 'getPayment'], [new JWTMiddleware($request), 'check']);

// ═══════════════════════════════════════════════════════════════════════════
// ─────── ADMIN ROUTES ────────────────────────────────────────────────────
// ═══════════════════════════════════════════════════════════════════════════

// ─────── Authentication & Profile ───────
$adminAuth = new AdminAuthController();
$router->post('/api/admin/login', [$adminAuth, 'login']);
$router->post('/api/admin/logout', [$adminAuth, 'logout'], null);
$router->get('/api/admin/user', [$adminAuth, 'user'], [new JWTMiddleware($request), 'check']);

// ─────── Dashboard & Statistics ───────
$statsCtrl = new AdminStatsController();
$router->get('/api/admin/stats', [$statsCtrl, 'index'], [new JWTMiddleware($request), 'check']);

// ─────── Vendor Management ───────

$vendorCtrl = new AdminVendorController();
$router->get('/api/admin/vendors', [$vendorCtrl, 'index'], [new JWTMiddleware($request), 'check']);
$router->post('/api/admin/vendors', [$vendorCtrl, 'store'], [new JWTMiddleware($request), 'check']);
$router->get('/api/admin/vendors/{id}', [$vendorCtrl, 'show'], [new JWTMiddleware($request), 'check']);
$router->post('/api/admin/vendors/{id}', [$vendorCtrl, 'updateVendorImage'], [new JWTMiddleware($request), 'check']);
$router->put('/api/admin/vendors/{id}', [$vendorCtrl, 'update'], [new JWTMiddleware($request), 'check']);
$router->delete('/api/admin/vendors/delete/{id}', [$vendorCtrl, 'delete'], [new JWTMiddleware($request), 'check']);
$router->get('/api/admin/vendors/{id}/earnings', [$vendorCtrl, 'earningByVendor'], [new JWTMiddleware($request), 'check']);
$router->get('/api/admin/vendors/{id}/orders', [$vendorCtrl, 'ordersByVendor'], [new JWTMiddleware($request), 'check']);

// ─────── Food Management ───────
$foodCtrl = new AdminFoodController();
$router->get('/api/admin/foods', [$foodCtrl, 'index'], [new JWTMiddleware($request), 'check']);
$router->post('/api/admin/foods', [$foodCtrl, 'store'], [new JWTMiddleware($request), 'check']);
$router->post('/api/admin/foods/image/{id}', [$foodCtrl, 'updateFoodImage'], [new JWTMiddleware($request), 'check']);
$router->get('/api/admin/foods/{id}', [$foodCtrl, 'show'], [new JWTMiddleware($request), 'check']);
$router->put('/api/admin/foods/{id}', [$foodCtrl, 'update'], [new JWTMiddleware($request), 'check']);
$router->delete('/api/admin/foods/{id}', [$foodCtrl, 'delete'], [new JWTMiddleware($request), 'check']);

// ─────── Inventory Management ───────
$router->get('/api/admin/foods/{id}/inventory', [$foodCtrl, 'getInventory'], [new JWTMiddleware($request), 'check']);
$router->patch('/api/admin/foods/{id}/inventory/adjust', [$foodCtrl, 'adjustInventory'], [new JWTMiddleware($request), 'check']);

// ─────── Order Management ───────
$orderCtrl = new AdminOrderController();
$router->get('/api/admin/orders', [$orderCtrl, 'index'], [new JWTMiddleware($request), 'check']);
$router->get('/api/admin/orders/{orderId}', [$orderCtrl, 'show'], [new JWTMiddleware($request), 'check']);
$router->patch('/api/admin/orders/{id}/status', [$orderCtrl, 'updateStatus'], [new JWTMiddleware($request), 'check']);

// ─────── Customer Management ───────
$customerCtrl = new AdminCustomerController();
$router->get('/api/admin/customers', [$customerCtrl, 'index'], [new JWTMiddleware($request), 'check']);
$router->post('/api/admin/customers', [$customerCtrl, 'store'], [new JWTMiddleware($request), 'check']);
$router->get('/api/admin/customers/{id}', [$customerCtrl, 'show'], [new JWTMiddleware($request), 'check']);
$router->put('/api/admin/customers/{id}', [$customerCtrl, 'update'], [new JWTMiddleware($request), 'check']);
$router->post('/api/admin/customers/image/{id}', [$customerCtrl, 'updateCustomerImage'], [new JWTMiddleware($request), 'check']);
$router->delete('/api/admin/customers/{id}', [$customerCtrl, 'delete'], [new JWTMiddleware($request), 'check']);

// ─────── Payment Management ───────
$paymentCtrl = new AdminPaymentController();
$router->get('/api/admin/payments', [$paymentCtrl, 'index'], [new JWTMiddleware($request), 'check']);
$router->get('/api/admin/payments/{id}', [$paymentCtrl, 'show'], [new JWTMiddleware($request), 'check']);

// ─────── System Settings ───────
$settingsCtrl = new AdminSettingsController();
$router->get('/api/admin/order-statuses', [$settingsCtrl, 'allStatuses'], [new JWTMiddleware($request), 'check']);
$router->post('/api/admin/order-statuses', [$settingsCtrl, 'createStatus'], [new JWTMiddleware($request), 'check']);
$router->get('/api/admin/order-statuses/{key}', [$settingsCtrl, 'getStatus'], [new JWTMiddleware($request), 'check']);
$router->put('/api/admin/order-statuses/{key}', [$settingsCtrl, 'updateStatus'], [new JWTMiddleware($request), 'check']);
$router->delete('/api/admin/order-statuses/{key}', [$settingsCtrl, 'deleteStatus'], [new JWTMiddleware($request), 'check']);
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