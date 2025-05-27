<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class Payment{
    private PDO $db;

    public function __construct(){
        $this->db = (new Database()) -> getConnection();
    }

    public function create(int $orderId,  string $stripePaymentId, float $amount, string $currency, string $status):bool{
        $sql = "
         INSERT INTO payments 
          (order_id, stripe_payment_id, amount, currency, status) 
          VALUES 
          (:order_id, 
          :stripe_id, 
          :amount, 
          :currency, 
          :status)";

        $stmt = $this->db->prepare($sql);

        $result = $stmt->execute([
            'order_id' => $orderId,
            'stripe_id' => $stripePaymentId,
            'amount' => $amount,
            'currency' => $currency,
            'status' => $status
        ]);

        return $result;
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