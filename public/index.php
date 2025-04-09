# public/index.php
<?php
require __DIR__.'/../vendor/autoload.php';

// Load environment variables
\App\Core\Config::load();

// Display PHP configuration information
phpinfo();

// Basic routing example
$request = $_SERVER['REQUEST_URI'];

switch ($request) {
    case '/':
        echo "Welcome to Food Delivery!";
        break;
    default:
        http_response_code(404);
        echo "Page not found";
}