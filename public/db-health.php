<?php
// Database Health Check for Connection Pooling
require __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;
use App\Core\Response;

\App\Core\Config::load();

try {
    // Test database connection
    $db = Database::getConnection();
    
    // Test a simple query
    $stmt = $db->query("SELECT 1 as test");
    $result = $stmt->fetch();
    
    // Get connection stats
    $stats = Database::getConnectionStats();
    
    // Response with health status
    Response::success('Database health check passed', [
        'status' => 'healthy',
        'connection_pooling' => $stats['pool_enabled'],
        'connection_count' => $stats['connection_count'],
        'is_connected' => $stats['is_connected'],
        'test_query_result' => $result['test'],
        'timestamp' => date('Y-m-d H:i:s')
    ])->json();
    
} catch (Exception $e) {
    Response::error('Database health check failed', [
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ], 500)->json();
}
