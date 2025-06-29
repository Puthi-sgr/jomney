<?php
namespace App\Models;

use App\Core\Database;
use PDO;

class PaymentMethod
{
    private PDO $db;

    public function __construct()
    {
         $this->db = Database::getConnection(); 
    }

    public function allByCustomer(int $customerId): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM payment_method WHERE customer_id = :customer_id ORDER BY created_at DESC"
        );
        $stmt->execute(['customer_id' => $customerId]);
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM payment_method WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function findByCustomerAndId(int $customerId, int $paymentMethodId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM payment_method WHERE id = :id AND customer_id = :customer_id"
        );
        $stmt->execute(['id' => $paymentMethodId, 'customer_id' => $customerId]);
        return $stmt->fetch() ?: null;
    }

    public function create(array $data): int|false
    {
        $sql = "INSERT INTO payment_method
                (customer_id, stripe_pm_id, type, card_brand, card_last4, exp_month, exp_year)
                VALUES
                (:customer_id, :stripe_pm_id, :type, :card_brand, :card_last4, :exp_month, :exp_year)
                RETURNING id";
        $stmt = $this->db->prepare($sql);
        
        $result = $stmt->execute([
            'customer_id'   => $data['customer_id'],
            'stripe_pm_id'  => $data['stripe_pm_id'],
            'type'          => $data['type'] ?? 'card',
            'card_brand'    => $data['card_brand'] ?? null,
            'card_last4'    => $data['card_last4'] ?? null,
            'exp_month'     => $data['exp_month'] ?? null,
            'exp_year'      => $data['exp_year'] ?? null,
        ]);

        return $result ? (int)$stmt->fetchColumn() : false;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM payment_method WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}