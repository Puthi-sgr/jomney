<?php

require __DIR__.'/../../vendor/autoload.php';

use App\Models\Customer;

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__, '/../../');
$dotenv->load();
\App\Core\Config::load();

class CustomerSeeder
{
    private Customer $customerModel;

    public function __construct()
    {
        $this->customerModel = new Customer();
    }

    public function run(): void
    {
        echo "Seeding customers...\n";

        $customers = [
            [
                'email' => 'john.doe@example.com',
                'password' => 'password123',
                'name' => 'John Doe',
                'address' => '123 Main Street, Downtown',
                'phone' => '+1234567890',
                'location' => 'Downtown Area',
                'lat_lng' => '40.7128',
                'image' => null
            ],
            [
                'email' => 'jane.smith@example.com',
                'password' => 'password123',
                'name' => 'Jane Smith',
                'address' => '456 Oak Avenue, Uptown',
                'phone' => '+1234567891',
                'location' => 'Uptown District',
                'lat_lng' => '40.7589',
                'image' => null
            ],
            [
                'email' => 'mike.johnson@example.com',
                'password' => 'password123',
                'name' => 'Mike Johnson',
                'address' => '789 Pine Road, Suburbs',
                'phone' => '+1234567892',
                'location' => 'Suburban Area',
                'lat_lng' => '40.6892',
                'image' => null
            ],
            [
                'email' => 'sarah.wilson@example.com',
                'password' => 'password123',
                'name' => 'Sarah Wilson',
                'address' => '321 Elm Street, City Center',
                'phone' => '+1234567893',
                'location' => 'City Center',
                'lat_lng' => '40.7282',
                'image' => null
            ],
            [
                'email' => 'david.brown@example.com',
                'password' => 'password123',
                'name' => 'David Brown',
                'address' => '654 Maple Drive, Westside',
                'phone' => '+1234567894',
                'location' => 'Westside',
                'lat_lng' => '40.7505',
                'image' => null
            ],
            [
                'email' => 'lisa.garcia@example.com',
                'password' => 'password123',
                'name' => 'Lisa Garcia',
                'address' => '987 Cedar Lane, Eastside',
                'phone' => '+1234567895',
                'location' => 'Eastside',
                'lat_lng' => '40.7614',
                'image' => null
            ],
            [
                'email' => 'robert.taylor@example.com',
                'password' => 'password123',
                'name' => 'Robert Taylor',
                'address' => '147 Birch Street, Northside',
                'phone' => '+1234567896',
                'location' => 'Northside',
                'lat_lng' => '40.7831',
                'image' => null
            ],
            [
                'email' => 'emily.davis@example.com',
                'password' => 'password123',
                'name' => 'Emily Davis',
                'address' => '258 Spruce Avenue, Southside',
                'phone' => '+1234567897',
                'location' => 'Southside',
                'lat_lng' => '40.6781',
                'image' => null
            ]
        ];

        $successCount = 0;
        $errorCount = 0;

        foreach ($customers as $customerData) {
            try {
                $result = $this->customerModel->create($customerData);
                if ($result) {
                    echo "✓ Created customer: {$customerData['name']} ({$customerData['email']})\n";
                    $successCount++;
                } else {
                    echo "✗ Failed to create customer: {$customerData['name']} ({$customerData['email']})\n";
                    $errorCount++;
                }
            } catch (Exception $e) {
                echo " Error creating customer {$customerData['name']}: " . $e->getMessage() . "\n";
                $errorCount++;
            }
        }

        echo "\n--- Seeding Summary ---\n";
        echo "Successfully created: {$successCount} customers\n";
        echo "Errors: {$errorCount}\n";
        echo "Total attempted: " . count($customers) . "\n";
        echo "Customer seeding completed!\n";
    }
}

// Run the seeder
$seeder = new CustomerSeeder();
$seeder->run();