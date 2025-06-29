<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class Payment{
    private PDO $db;

    public function __construct(){
         $this->db = Database::getConnection(); 
    }

    public function all():array{
        $sql = "SELECT p.*, pm.type as payment_type, pm.card_last4, o.total_amount as order_total
                FROM payment p
                JOIN payment_method pm ON p.payment_method_id = pm.id
                JOIN orders o ON p.order_id = o.id
                ORDER BY p.created_at DESC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array {
        $stmt = $this->db->prepare("SELECT p.*, pm.type as payment_type, pm.card_last4, o.total_amount as order_total
                                   FROM payment p
                                   JOIN payment_method pm ON p.payment_method_id = pm.id
                                   JOIN orders o ON p.order_id = o.id
                                   WHERE p.id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function findByOrderId(int $orderId): ?array {
        $stmt = $this->db->prepare("SELECT p.*, pm.type as payment_type, pm.card_last4
                                   FROM payment p
                                   JOIN payment_method pm ON p.payment_method_id = pm.id
                                   WHERE p.order_id = :order_id");
        $stmt->execute(['order_id' => $orderId]);
        return $stmt->fetch() ?: null;
    }

    public function findByCustomer(int $customerId): array {
        $stmt = $this->db->prepare("SELECT p.*, pm.type as payment_type, pm.card_last4, o.total_amount as order_total
                                   FROM payment p
                                   JOIN payment_method pm ON p.payment_method_id = pm.id
                                   JOIN orders o ON p.order_id = o.id
                                   WHERE pm.customer_id = :customer_id
                                   ORDER BY p.created_at DESC");
        $stmt->execute(['customer_id' => $customerId]);
        return $stmt->fetchAll();
    }

   public function create(array $data): int|false
    {
        $sql = "INSERT INTO payment
                (order_id, payment_method_id, stripe_payment_id, amount, currency, status)
                VALUES
                (:order_id, :payment_method_id, :stripe_payment_id, :amount, :currency, :status)
                RETURNING id";
        $stmt = $this->db->prepare($sql);
        
        $result = $stmt->execute([
            'order_id'           => $data['order_id'],
            'payment_method_id'  => $data['payment_method_id'],
            'stripe_payment_id'  => $data['stripe_payment_id'],
            'amount'             => $data['amount'],
            'currency'           => $data['currency'] ?? 'usd',
            'status'             => $data['status'] ?? 'pending',
        ]);

        return $result ? (int)$stmt->fetchColumn() : false;
    }

    public function updateStatus(int $paymentId, string $newStatus): bool {
        $sql = "UPDATE payment SET status = :status, updated_at = NOW() WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'status' => $newStatus,
            'id' => $paymentId
        ]);
    }

    public function updateStatusByStripeId(string $stripePaymentId, string $newStatus): bool {
        $sql = "UPDATE payment SET status = :status, updated_at = NOW() WHERE stripe_payment_id = :stripe_id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'status' => $newStatus,
            'stripe_id' => $stripePaymentId
        ]);
    }
}
