<?php
namespace Tests;

use PHPUnit\Framework\TestCase;
require_once __DIR__ . '/../app/Controllers/public/PublicController.php';
use App\Controllers\Public\PublicController;
use App\Models\Vendor;
use App\Models\Food;
use App\Core\Response;

class PublicControllerTest extends TestCase
{
    private function inject(object $obj, string $prop, $value): void
    {
        $ref = new \ReflectionClass($obj);
        $property = $ref->getProperty($prop);
        $property->setAccessible(true);
        $property->setValue($obj, $value);
    }

    public function testGetAllVendorsReturnsSanitizedData(): void
    {
        $vendors = [
            [
                'id' => 1,
                'email' => 'v@example.com',
                'password' => 'hash',
                'name' => 'Vendor',
                'food_types' => '[]'
            ]
        ];
        $foods = [
            [
                'id' => 5,
                'vendor_id' => 1,
                'name' => 'Pizza',
                'qty_available' => 12,
                'created_at' => '',
                'updated_at' => ''
            ]
        ];

        $vendorModel = $this->getMockBuilder(Vendor::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['all'])
            ->getMock();
        $vendorModel->expects($this->any())
            ->method('all')
            ->willReturn($vendors);

        $foodModel = $this->getMockBuilder(Food::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['allByVendorIds'])
            ->getMock();
        $foodModel->expects($this->once())
            ->method('allByVendorIds')
            ->with([1])
            ->willReturn([1 => $foods]);

        $controller = (new \ReflectionClass(PublicController::class))->newInstanceWithoutConstructor();
        $this->inject($controller, 'vendorModel', $vendorModel);
        $this->inject($controller, 'foodModel', $foodModel);

        $response = $controller->getAllVendors();

        $this->assertEquals(200, $response->status());
        $body = $response->body();
        $this->assertTrue($body['success']);
        $vendor = $body['data']['vendors'][0];
        $this->assertArrayNotHasKey('email', $vendor);
        $this->assertArrayNotHasKey('password', $vendor);
        $this->assertArrayHasKey('foods', $vendor);
        $food = $vendor['foods'][0];
        $this->assertArrayNotHasKey('vendor_id', $food);
        $this->assertArrayNotHasKey('qty_available', $food);
        $this->assertArrayHasKey('stock_qty', $food);
        $this->assertSame(12, $food['stock_qty']);
    }
}
