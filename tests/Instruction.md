## Test Suite Instructions

## Requirements
- PHP and Composer installed
- Run `composer install` once to install dependencies

## Running the Tests
1. From the project root, refresh autoload files:
   ```sh
   composer dump-autoload
   ```
2. Execute the PHPUnit test suite:
   ```sh
   php vendor/bin/phpunit -d memory_limit=512M
   ```
   The command runs all tests in the `tests` directory.

## Testing Specific Files or Methods
- To run tests in a specific file:
  ```sh
  php vendor/bin/phpunit --filter <TestClassName> tests/<TestFileName>.php -d memory_limit=512M
  ```
  Replace `<TestClassName>` with the name of the test class and `<TestFileName>` with the name of the test file.
- To run a specific test method within a file:
  ```sh
  php vendor/bin/phpunit --filter <TestClassName>::<TestMethodName> tests/<TestFileName>.php -d memory_limit=512M
  ```
  Replace `<TestClassName>` with the name of the test class, `<TestMethodName>` with the name of the test method, and `<TestFileName>` with the name of the test file.

## Test Coverage
- **AdminControllerTest** – mocks the admin model and request to verify that login returns a JWT token and that profile data omits sensitive fields.
- **CustomerControllerTest** – stubs customer model methods to ensure registration and login generate tokens and correct HTTP statuses.
- **PublicControllerTest** – fakes vendor and food models so `getAllVendors` returns sanitized data without passwords or vendor IDs.
- **ApiTest** – reaches out to an external API; it may fail without network access. Use PHPUnit's `--filter` option to run a specific test class if needed.

Each test isolates controller dependencies with PHPUnit mocks, allowing the responses to be validated without hitting a real database or API.