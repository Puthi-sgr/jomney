<?php
// app/Models/OrderStatus.php
namespace App\Models;

use App\Core\Database;
use PDO;

class OrderStatus
{
    private PDO $db;
    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }
    public function all(): array
    {
        $stmt = $this->db->query("SELECT * FROM order_statuses ORDER BY id");
        return $stmt->fetchAll();
    }
    public function findByKey(string $key): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM order_statuses WHERE key = :key");
        $stmt->execute(['key' => $key]);
        return $stmt->fetch() ?: null;
    }

    public function create(string $key, string $label): bool{
        $sql = "INSERT INTO order_statuses (key, label) VALUES (:key, :label)";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'key' => $key,
            'label' => $label
        ]);
    }

    public function update(string $key): bool{
        $sql = "UPDATE order_statuses SET label = :label WHERE key = :key";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'key' => $key,
        ]);
    }

    public function delete(string $key):bool {
        $sql = "DELETE FROM order_statuses WHERE key = :key";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'key' => $key,
        ]);
    }
}