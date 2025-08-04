<?php
namespace Tests;

use PHPUnit\Framework\TestCase;
use App\Controllers\Customer\CustomerAuthController;
use App\Core\Response;
use App\Core\JWTService;
use App\Models\Customer;
use App\Core\Request;

class CustomerControllerTest extends TestCase
{
    private function inject(object $obj, string $prop, $value): void
    {
        $ref = new \ReflectionClass($obj);
        $property = $ref->getProperty($prop);
        $property->setAccessible(true);
        $property->setValue($obj, $value);
    }

    public function testRegisterCreatesCustomer(): void
    {
        $_ENV['JWT_SECRET'] = 'secret';
        JWTService::init();

        $customerModel = $this->getMockBuilder(Customer::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['findByEmail','create'])
            ->getMock();
        $customerModel->expects($this->any())
            ->method('findByEmail')
            ->willReturn(null);
        $customerModel->expects($this->any())
            ->method('create')
            ->willReturn(2);

        $requestStub = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['all'])
            ->getMock();
        $requestStub->expects($this->any())
            ->method('all')
            ->willReturn([
            'email' => 'user@example.com',
            'password' => 'secret1',
            'name' => 'John'
        ]);

        $controller = (new \ReflectionClass(CustomerAuthController::class))->newInstanceWithoutConstructor();
        $this->inject($controller, 'customerModel', $customerModel);
        $this->inject($controller, 'request', $requestStub);

        $response = $controller->register();

        $this->assertEquals(201, $response->status());
        $body = $response->body();
        $this->assertTrue($body['success']);
        $this->assertEquals(2, $body['data']['user_id']);
        $this->assertArrayHasKey('token', $body['data']);
    }

    public function testLoginSucceedsWithValidCredentials(): void
    {
        $_ENV['JWT_SECRET'] = 'secret';
        JWTService::init();

        $hashed = password_hash('mypassword', PASSWORD_DEFAULT);
        $customerModel = $this->getMockBuilder(Customer::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['findByEmail'])
            ->getMock();
        $customerModel->expects($this->any())
            ->method('findByEmail')
            ->willReturn([
            'id' => 5,
            'email' => 'me@example.com',
            'password' => $hashed
        ]);

        $requestStub = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['all'])
            ->getMock();
        $requestStub->expects($this->any())
            ->method('all')
            ->willReturn([
            'email' => 'me@example.com',
            'password' => 'mypassword'
        ]);

        $controller = (new \ReflectionClass(CustomerAuthController::class))->newInstanceWithoutConstructor();
        $this->inject($controller, 'customerModel', $customerModel);
        $this->inject($controller, 'request', $requestStub);

        $response = $controller->login();

        $this->assertEquals(200, $response->status());
        $this->assertTrue($response->body()['success']);
        $this->assertArrayHasKey('token', $response->body()['data']);
    }
}
