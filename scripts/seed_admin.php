<?php
require_once __DIR__ . '/../app/Core/Database.php';
use App\Core\Database;

$db = (new Database())->getConnection();

// Check if admin already exists
$checkStmt = $db->prepare("SELECT COUNT(*) FROM admin WHERE email = :email");
$checkStmt->execute(['email' => 'admin@example.com']);
$exists = $checkStmt->fetchColumn();

if ($exists > 0) {
    echo "Admin user already exists!\n";
    exit;
}

// Create admin only if doesn't exist
$stmt = $db->prepare(
    "INSERT INTO admin (email, password, name, created_at) 
     VALUES (:email, :password, :name, NOW())"
);

$result = $stmt->execute([
    'email' => 'systemAdmin@example.com',
    'password' => password_hash('AdminPassword123', PASSWORD_DEFAULT),
    'name' => 'System Admin'
]);

echo $result ? "Admin created successfully!\n" : "Failed to create admin.\n";
?>