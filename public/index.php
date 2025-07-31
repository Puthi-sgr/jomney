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
use App\Middleware\CacheMiddleware;
use App\Core\RedisService;
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

$jwtMiddleware = new JWTMiddleware($request);

//Dependency injection for Redis
$redis = new RedisService();
$cacheMiddleware = new CacheMiddleware($redis, $request);
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
$router->post('/api/v1/auth/logout', [$customerAuth, 'logout'], [$jwtMiddleware, 'handle']);
$router->get('/api/v1/auth/profile', [$customerAuth, 'profile'], [$jwtMiddleware, 'handle']);
$router->put('/api/v1/auth/profile', [$customerAuth, 'updateProfile'], [$jwtMiddleware, 'handle']);
$router->post('/api/v1/auth/profile/image', [$customerAuth, 'updateCustomerProfilePicture'], [$jwtMiddleware, 'handle']);

// ─────── Order Management ───────

$customerOrder = new CustomerOrderController();

$router->post('/api/v1/orders', [$customerOrder, 'store'], [$jwtMiddleware, 'handle']);
$router->get('/api/v1/orders', [$customerOrder, 'index'], [$jwtMiddleware, 'handle']);
$router->get('/api/v1/orders/{id}', [$customerOrder, 'show'], [$jwtMiddleware, 'handle']);
$router->delete('/api/v1/orders/{id}', [$customerOrder, 'cancel'], [$jwtMiddleware, 'handle']);

// ─────── Payment Management ───────

$customerPayment = new CustomerPaymentController();

// Payment Methods Management (Stripe Only)
$router->get('/api/v1/payment-methods', [$customerPayment, 'getPaymentMethods'], [$jwtMiddleware, 'handle']);
$router->post('/api/v1/payment-methods/stripe/setup-intent', [$customerPayment, 'createSetupIntent'], [$jwtMiddleware, 'handle']);
$router->post('/api/v1/payment-methods/stripe/save', [$customerPayment, 'savePaymentMethod'], [$jwtMiddleware, 'handle']);
$router->delete('/api/v1/payment-methods/stripe/{id}', [$customerPayment, 'removeStripePaymentMethod'], [$jwtMiddleware, 'handle']);

//stripe payment processing
$router->post('/api/v1/orders/{orderid}/stripe-payment', [$customerPayment, 'processStripePayment'], [$jwtMiddleware, 'handle']);
// Payment History
$router->get('/api/v1/payments', [$customerPayment, 'getPaymentHistory'], [$jwtMiddleware, 'handle']);
$router->get('/api/v1/payments/{id}', [$customerPayment, 'getPayment'], [$jwtMiddleware, 'handle']);

// ═══════════════════════════════════════════════════════════════════════════
// ─────── ADMIN ROUTES ────────────────────────────────────────────────────
// ═══════════════════════════════════════════════════════════════════════════

// ─────── Authentication & Profile ───────
$adminAuth = new AdminAuthController();
$router->post('/api/admin/login', [$adminAuth, 'login']);
$router->post('/api/admin/logout', [$adminAuth, 'logout'], null);
$router->get('/api/admin/user', [$adminAuth, 'user'], [$jwtMiddleware, 'handle']);

// ─────── Dashboard & Statistics ───────
$statsCtrl = new AdminStatsController();
$router->get('/api/admin/stats', [$statsCtrl, 'index'],  $jwtMiddleware);

// ─────── Vendor Management ───────

$vendorCtrl = new AdminVendorController();
$router->get('/api/admin/vendors', [$vendorCtrl, 'index'], [$jwtMiddleware, 'handle']);
$router->post('/api/admin/vendors', [$vendorCtrl, 'store'], [$jwtMiddleware, 'handle']);
$router->get('/api/admin/vendors/{id}', [$vendorCtrl, 'show'], [$jwtMiddleware, 'handle']);
$router->post('/api/admin/vendors/{id}', [$vendorCtrl, 'updateVendorImage'], [$jwtMiddleware, 'handle']);
$router->put('/api/admin/vendors/{id}', [$vendorCtrl, 'update'], [$jwtMiddleware, 'handle']);
$router->delete('/api/admin/vendors/delete/{id}', [$vendorCtrl, 'delete'], [$jwtMiddleware, 'handle']);
$router->get('/api/admin/vendors/{id}/earnings', [$vendorCtrl, 'earningByVendor'], [$jwtMiddleware, 'handle']);
$router->get('/api/admin/vendors/{id}/orders', [$vendorCtrl, 'ordersByVendor'], [$jwtMiddleware, 'handle']);

// ─────── Food Management ───────
$foodCtrl = new AdminFoodController();
$router->get('/api/admin/foods', [$foodCtrl, 'index'], [$jwtMiddleware, 'handle']);
$router->post('/api/admin/foods', [$foodCtrl, 'store'], [$jwtMiddleware, 'handle']);
$router->post('/api/admin/foods/image/{id}', [$foodCtrl, 'updateFoodImage'], [$jwtMiddleware, 'handle']);
$router->get('/api/admin/foods/{id}', [$foodCtrl, 'show'], [$jwtMiddleware, 'handle']);
$router->put('/api/admin/foods/{id}', [$foodCtrl, 'update'], [$jwtMiddleware, 'handle']);
$router->delete('/api/admin/foods/{id}', [$foodCtrl, 'delete'], [$jwtMiddleware, 'handle']);

// ─────── Inventory Management ───────
$router->get('/api/admin/foods/{id}/inventory', [$foodCtrl, 'getInventory'], [$jwtMiddleware, 'handle']);
$router->patch('/api/admin/foods/{id}/inventory/adjust', [$foodCtrl, 'adjustInventory'], [$jwtMiddleware, 'handle']);

// ─────── Order Management ───────
$orderCtrl = new AdminOrderController();
$router->get('/api/admin/orders', [$orderCtrl, 'index'], [$jwtMiddleware, 'handle']);
$router->get('/api/admin/orders/{orderId}', [$orderCtrl, 'show'], [$jwtMiddleware, 'handle']);
$router->patch('/api/admin/orders/{id}/status', [$orderCtrl, 'updateStatus'], [$jwtMiddleware, 'handle']);

// ─────── Customer Management ───────
$customerCtrl = new AdminCustomerController();
$router->get('/api/admin/customers', [$customerCtrl, 'index'], [$jwtMiddleware, 'handle']);
$router->post('/api/admin/customers', [$customerCtrl, 'store'], [$jwtMiddleware, 'handle']);
$router->get('/api/admin/customers/{id}', [$customerCtrl, 'show'], [$jwtMiddleware, 'handle']);
$router->put('/api/admin/customers/{id}', [$customerCtrl, 'update'], [$jwtMiddleware, 'handle']);
$router->post('/api/admin/customers/image/{id}', [$customerCtrl, 'updateCustomerImage'], [$jwtMiddleware, 'handle']);
$router->delete('/api/admin/customers/{id}', [$customerCtrl, 'delete'], [$jwtMiddleware, 'handle']);

// ─────── Payment Management ───────
$paymentCtrl = new AdminPaymentController();
$router->get('/api/admin/payments', [$paymentCtrl, 'index'], [$jwtMiddleware, 'handle']);
$router->get('/api/admin/payments/{id}', [$paymentCtrl, 'show'], [$jwtMiddleware, 'handle']);

// ─────── System Settings ───────
$settingsCtrl = new AdminSettingsController();
$router->get('/api/admin/order-statuses', [$settingsCtrl, 'allStatuses'], [$jwtMiddleware, 'handle']);
$router->post('/api/admin/order-statuses', [$settingsCtrl, 'createStatus'], [$jwtMiddleware, 'handle']);
$router->get('/api/admin/order-statuses/{key}', [$settingsCtrl, 'getStatus'], [$jwtMiddleware, 'handle']);
$router->put('/api/admin/order-statuses/{key}', [$settingsCtrl, 'updateStatus'], [$jwtMiddleware, 'handle']);
$router->delete('/api/admin/order-statuses/{key}', [$settingsCtrl, 'deleteStatus'], [$jwtMiddleware, 'handle']);
/* ------------------AUTHs---------------------------------- */

/* ---------------------ORDERS---------------------------- */

/* ---------------------MENU---------------------------- */

/* ---------------------PAYMENTS---------------------------- */
$paymentController = new PaymentController();
//must be logged in to create payment
$router->post("/payments/create", [$paymentController, 'create'], [JWTMiddleware::class, 'handle']);

//Webhook endpoint (Public, no auth)
$router->post('/payments/webhook', [$paymentController, 'webhook']);


// Basic routing example
$requestMethod = $_SERVER['REQUEST_METHOD'];
//provide the uri requested by the client
$request = $_SERVER['REQUEST_URI']; //tell the app how to handle the request

set_exception_handler([ErrorHandler::class, 'handleException']);
//The error handler return a response object but cannot display json
// so we need to call the json method from the Response class



$router->dispatch($requestMethod, $request);