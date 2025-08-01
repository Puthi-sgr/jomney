<?php
namespace App\Controllers\Admin;

use App\Core\Response;
use App\Core\Database;
use PDO;
use App\Core\Request;

class AdminStatsController
{
    private PDO $db;
    private Request $request;

    public function __construct()
    {
         $this->request = new Request();
         $this->db = Database::getConnection();
    }

    /**
     * GET /api/admin/stats
     * Returns overall counts / sums needed for dashboard charts:
     *  - total_customers
     *  - total_vendors
     *  - total_orders
     *  - total_revenue
     *  - orders_by_status (array)
     */
     public function index(): Response
    {
        error_log("AdminStatsController invoked CACHE MISSSSSSSSSSSSSSSS");
         // 1) Total customers
        $custStmt = $this->db->query("SELECT COUNT(*) FROM customer");
        $totalCustomers = (int) $custStmt->fetchColumn();
 
        // 2) Total vendors
        $vendStmt = $this->db->query("SELECT COUNT(*) FROM vendor");
        $totalVendors = (int) $vendStmt->fetchColumn();

        // 3) Total orders
        $ordStmt = $this->db->query("SELECT COUNT(*) FROM orders");
        $totalOrders = (int) $ordStmt->fetchColumn();

        // 4) Total revenue (sum of all successful payments)
        $revStmt = $this->db->query("
            SELECT COALESCE(SUM(amount),0) 
            FROM payment 
            WHERE status = 'succeeded'
        ");
        $totalRevenue = (float) $revStmt->fetchColumn();

        // 5) Orders by status (key + count)
        $statusStmt = $this->db->query("
            SELECT os.key, COUNT(o.id) AS count
            FROM orders o
            JOIN order_statuses os ON o.status_id = os.id
            GROUP BY os.key
        ");
  	    $ordersByStatus = $statusStmt->fetchAll(PDO::FETCH_KEY_PAIR);
        //First column is the key, second column is the value
        // Database returns:
        // | key       | count |
        // |-----------|-------|
        // | pending   | 12    |
        // | confirmed | 5     |
        // | delivered | 8     |
         // e.g. [ 'pending'=>12, 'confirmed'=>5, ... ]

        // 6) Return everything in one JSON
        return Response::success('Stats overview', [
            'total_customers' => $totalCustomers,
            'total_vendors'   => $totalVendors,
            'total_orders'    => $totalOrders,
            'total_revenue'   => $totalRevenue,
            'orders_by_status'=> $ordersByStatus,
        ]);
    }
}