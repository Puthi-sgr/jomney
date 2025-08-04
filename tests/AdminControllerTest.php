<?php
namespace Tests;

use PHPUnit\Framework\TestCase;
use App\Controllers\Admin\AdminAuthController;
use App\Core\Response;
use App\Core\JWTService;
use App\Models\Admin;
use App\Core\Request;

class AdminControllerTest extends TestCase
{
    private function inject(object $obj, string $prop, $value): void
    {
        $ref = new \ReflectionClass($obj);
        $property = $ref->getProperty($prop);
        $property->setAccessible(true);
        $property->setValue($obj, $value);
    }

    public function testLoginSuccessReturnsToken(): void
    {
        $_ENV['JWT_SECRET'] = 'testsecret';
        JWTService::init();

        $adminData = [
            'id' => 1,
            'email' => 'admin@example.com',
            'password' => password_hash('pass', PASSWORD_DEFAULT)
        ];
        $adminModel = $this->getMockBuilder(Admin::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['findByEmail'])
            ->getMock();
        $adminModel->expects($this->any())
            ->method('findByEmail')
            ->willReturn($adminData);

        $requestStub = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isJson','input'])
            ->getMock();
        $requestStub->expects($this->any())
            ->method('isJson')
            ->willReturn(true);
        $requestStub->expects($this->any())
            ->method('input')
            ->willReturnCallback(function($key,$default=null){
            return ['email'=>'admin@example.com','password'=>'pass'][$key] ?? $default;
        });

        $controller = (new \ReflectionClass(AdminAuthController::class))->newInstanceWithoutConstructor();
        $this->inject($controller, 'adminModel', $adminModel);
        $this->inject($controller, 'request', $requestStub);
        $this->inject($controller, 'response', new Response());

        $response = $controller->login();

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->status());
        $body = $response->body();
        $this->assertTrue($body['success']);
        $this->assertArrayHasKey('token', $body['data']);
    }

    public function testUserProfileReturnsData(): void
    {
        $adminData = [
            'id' => 1,
            'email' => 'admin@example.com',
            'password' => 'hash',
            'name' => 'Admin'
        ];
        $adminModel = $this->getMockBuilder(Admin::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['find'])
            ->getMock();
        $adminModel->expects($this->any())
            ->method('find')
            ->willReturn($adminData);

        $controller = (new \ReflectionClass(AdminAuthController::class))->newInstanceWithoutConstructor();
        $this->inject($controller, 'adminModel', $adminModel);
        $requestMock = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->inject($controller, 'request', $requestMock);

        $_SERVER['user_id'] = 1;
        $response = $controller->user();

        $this->assertEquals(200, $response->status());
        $this->assertTrue($response->body()['success']);
        $this->assertEquals('Admin profile', $response->body()['message']);
        $this->assertArrayNotHasKey('password', $response->body()['data']);
    }
}
