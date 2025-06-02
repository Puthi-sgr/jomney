<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class Payment{
    private PDO $db;

    public function __construct(){
        $this->db = (new Database()) -> getConnection();
    }

   public function create(array $data): bool
    {
        $sql = "INSERT INTO payment
                (order_id, payment_method_id, stripe_payment_id, amount, currency, status)
                VALUES
                (:order_id, :payment_method_id, :stripe_payment_id, :amount, :currency, :status)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'order_id'           => $data['order_id'],
            'payment_method_id'  => $data['payment_method_id'],
            'stripe_payment_id'  => $data['stripe_payment_id'],
            'amount'             => $data['amount'],
            'currency'           => $data['currency'],
            'status'             => $data['status'],
        ]);
    }

    public function updateStatus(string $stripePaymentId, string $newStatus):bool{
        $sql = "UPDATE payments SET status = :status WHERE stripe_payment_id = :stripe_id";

        $stmt = $this->db->prepare($sql);

        $result = $stmt->execute([
            'status' => $newStatus,
            'stripe_id' => $stripePaymentId
        ]);

        return $result;
    }
}   