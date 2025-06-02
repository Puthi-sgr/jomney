<?php
// app/Models/PaymentMethod.php
namespace App\Models;

use App\Core\Database;
use PDO;

class PaymentMethod
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

     public function allByCustomer(int $customerId): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM payment_method WHERE customer_id = :customer_id"
        );
        $stmt->execute(['customer_id' => $customerId]);
        return $stmt->fetchAll();
    }

     public function create(array $data): bool
    {
        $sql = "INSERT INTO payment_method
                (customer_id, stripe_pm_id, type, card_brand, card_last4, exp_month, exp_year)
                VALUES
                (:customer_id, :stripe_pm_id, :type, :card_brand, :card_last4, :exp_month, :exp_year)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'customer_id'   => $data['customer_id'],
            'stripe_pm_id'  => $data['stripe_pm_id'],
            'type'          => $data['type'],
            'card_brand'    => $data['card_brand'],
            'card_last4'    => $data['card_last4'],
            'exp_month'     => $data['exp_month'],
            'exp_year'      => $data['exp_year'],
        ]);
    }
}