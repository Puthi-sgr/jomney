# Agent Guide for Jomney Food Delivery System

## Build/Test Commands
- Install dependencies: `composer install`
- Autoload dump: `composer dump-autoload`

## Architecture Overview
- **Framework**: Custom PHP MVC with PSR-4 autoloading
- **Database**: PostgreSQL with custom models
- **Authentication**: JWT with Firebase JWT library
- **Payment**: Stripe integration with saved payment methods
- **Entry Point**: `public/index.php` handles all routing
- **Core Classes**: Router, Response, JWTService, ErrorHandler
- **Middleware**: AdminMiddleware, CustomerMiddleware, JWTMiddleware, CorsMiddleware

## Code Style & Conventions
- **Namespaces**: `App\Controllers\{Role}\`, `App\Models\`, `App\Core\`
- **Naming**: PascalCase for classes, camelCase for methods/properties
- **Response Format**: Use `Response::success()` and `Response::error()` for all API responses
- **Error Handling**: Try-catch blocks with Response::error() for exceptions
- **Authentication**: Use `$_SERVER['user_id']` for authenticated user context
- **Models**: Constructor injection for dependencies, typed properties
- **Controllers**: Dependency injection in constructor, void return types for endpoints
- **Database**: Use parameterized queries, no direct SQL concatenation
- **Middleware**: Applied via Router, checks JWT tokens and sets user context
- **File Organization**: Group by role (Admin/, Customer/, Public/)
