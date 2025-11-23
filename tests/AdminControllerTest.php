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
    private function inject(object $obj, string $prop, $value): void //(instance of the class, the variable u want to replace, the value u want to set)
    {
        $ref = new \ReflectionClass($obj); //Allow you to access private/protected properties and override methods
        $property = $ref->getProperty($prop); //Get the variable you want to replace
        $property->setAccessible(true); //Set the variable to be accessible
        $property->setValue($obj, $value); //Set the value of the variable
    }

    public function testLoginSuccessReturnsToken(): void
    {
        $_ENV['JWT_SECRET'] = 'testsecret'; //Override the JWT secret for testing
        //why?
        // Keep consistent, isolation, less dependencies, easier to test
        JWTService::init(); //Initialize the fake jwt service

        $adminData = [
            'id' => 1,
            'email' => 'admin@example.com',
            'password' => password_hash('pass', PASSWORD_DEFAULT)
        ]; // Mock admin data  

        $adminModel = $this->getMockBuilder(Admin::class) //Start the process of mocking the Admin model
            ->disableOriginalConstructor() //Stop the admin construct to do sth -> avoid connecting the database
            ->onlyMethods(['findByEmail']) //This method will be mocked its a fake method
            ->getMock();  //Finalize the object creation

        $adminModel->expects($this->any())
            ->method('findByEmail')
            ->willReturn($adminData); //When the findByEmail method is called, it should return the mocked data

        $requestStub = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isJson','input'])
            ->getMock();

        $requestStub->expects($this->any())
            ->method('isJson')
            ->willReturn(true);

        //if the input method is called, it should return the mocked admin data
        $requestStub->expects($this->any())
            ->method('input')
            ->willReturnCallback(function($key,$default=null){
            return ['email'=>'admin@example.com','password'=>'pass'][$key] ?? $default;
        });

        $controller = (new \ReflectionClass(AdminAuthController::class))->newInstanceWithoutConstructor(); //initialize the controller object without calling the constructor
        $this->inject($controller, 'adminModel', $adminModel); //Inject the mocked admin model into the controller
        $this->inject($controller, 'request', $requestStub);
        $this->inject($controller, 'response', new Response());

        $response = $controller->login();

        $this->assertInstanceOf(Response::class, $response); //Expect the response to be an instance of the Response class
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

        $this->inject($controller, 'request', $requestMock); //Inject the mocked request object

        $_SERVER['user_id'] = 1;
        $response = $controller->user(); //Response wil return the actual response from the user method

        $this->assertEquals(200, $response->status());
        $this->assertTrue($response->body()['success']);
        $this->assertEquals('Admin profile', $response->body()['message']);
        $this->assertArrayNotHasKey('password', $response->body()['data']);
    }
}
