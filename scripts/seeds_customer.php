<?php
require_once __DIR__ . '/../app/Core/Database.php';
use App\Core\Database;

$db = (new Database())->getConnection();

// Sample customers data
$customers = [
    [
        'email' => 'john.doe@example.com',
        'password' => 'CustomerPass123',
        'name' => 'John Doe',
        'address' => '123 Main Street, Downtown',
        'phone' => '+1234567890',
        'location' => 'Downtown Area',
        'lat_lng' => '40.7128000'
    ],
    [
        'email' => 'jane.smith@example.com',
        'password' => 'CustomerPass123',
        'name' => 'Jane Smith',
        'address' => '456 Oak Avenue, Uptown',
        'phone' => '+1234567891',
        'location' => 'Uptown District',
        'lat_lng' => '40.7589000'
    ],
    [
        'email' => 'mike.johnson@example.com',
        'password' => 'CustomerPass123',
        'name' => 'Mike Johnson',
        'address' => '789 Pine Road, Suburbs',
        'phone' => '+1234567892',
        'location' => 'Suburban Area',
        'lat_lng' => '40.6892000'
    ],
    [
        'email' => 'sarah.wilson@example.com',
        'password' => 'CustomerPass123',
        'name' => 'Sarah Wilson',
        'address' => '321 Elm Street, City Center',
        'phone' => '+1234567893',
        'location' => 'City Center',
        'lat_lng' => '40.7282000'
    ],
    [
        'email' => 'david.brown@example.com',
        'password' => 'CustomerPass123',
        'name' => 'David Brown',
        'address' => '654 Maple Drive, Westside',
        'phone' => '+1234567894',
        'location' => 'Westside',
        'lat_lng' => '40.7505000'
    ]
];

$successCount = 0;
$skipCount = 0;

foreach ($customers as $customerData) {
    // Check if customer already exists
    $checkStmt = $db->prepare("SELECT COUNT(*) FROM customer WHERE email = :email");
    $checkStmt->execute(['email' => $customerData['email']]);
    $exists = $checkStmt->fetchColumn();

    if ($exists > 0) {
        echo "Customer {$customerData['name']} ({$customerData['email']}) already exists!\n";
        $skipCount++;
        continue;
    }

    // Create customer only if doesn't exist
    $stmt = $db->prepare(
        "INSERT INTO customer (email, password, name, address, phone, location, lat_lng, created_at, updated_at) 
         VALUES (:email, :password, :name, :address, :phone, :location, :lat_lng, NOW(), NOW())"
    );

    $result = $stmt->execute([
        'email' => $customerData['email'],
        'password' => password_hash($customerData['password'], PASSWORD_DEFAULT),
        'name' => $customerData['name'],
        'address' => $customerData['address'],
        'phone' => $customerData['phone'],
        'location' => $customerData['location'],
        'lat_lng' => $customerData['lat_lng']
    ]);

    if ($result) {
        echo "Customer {$customerData['name']} created successfully!\n";
        $successCount++;
    } else {
        echo "Failed to create customer {$customerData['name']}.\n";
    }
}

echo "\n--- Summary ---\n";
echo "Created: {$successCount} customers\n";
echo "Skipped: {$skipCount} customers (already exist)\n";
echo "Total: " . ($successCount + $skipCount) . " customers processed\n";
?>
