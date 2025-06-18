<?php
// app/Models/Customer.php
namespace App\Models;

use App\Core\Database;
use PDO;


class Customer{
    private PDO $db;
    
    public function __construct(){
          $this->db = (new Database())->getConnection();
    }

    public function all():array{
        $sql = "SELECT * FROM customer";
        $stmt = $this->db->query($sql);

        return $stmt->fetchAll();
    }
    public function find(int $id): ?array
    {
        //1. Prepare the statement with parameters
        //2. Execute the statement with parameters
        //3. Fetch the result
        $stmt = $this->db->prepare("SELECT * FROM customer WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function findByEmail(string $email): ?array
    {
        //1. Prepare the statement with parameters
        //2. Execute the statement with parameters
        //3. Fetch the result
        $stmt = $this->db->prepare("SELECT * FROM customer WHERE email = :email");
        $stmt->execute(['email' => $email]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Creates a new customer record in the database
     * 
     * @param array $data Customer data containing:
     *                    - email (string, required, unique)
     *                    - password (string, required)
     *                    - name (string, required)
     *                    - address (string, optional)
     *                    - phone (string, optional)
     *                    - location (string, optional)
     *                    - lat_lng (string, optional)
     * @return bool True if creation was successful, false otherwise
     */
    public function create(array $data): bool{

            $sql = "INSERT INTO customer
                (email, password, name, address, phone, location, lat_lng)
                VALUES
                (:email, :password, :name, :address, :phone, :location, :lat_lng)";
            
            $stmt = $this->db->prepare($sql);

            return $stmt->execute([
                'email'     => $data['email'],            // unique
                'password'  => password_hash($data['password'], PASSWORD_DEFAULT),
                'name'      => $data['name'],
                'address'   => $data['address'] ?? null,
                'phone'     => $data['phone'] ?? null,
                'location'  => $data['location'] ?? null,
                'lat_lng'   => $data['lat_lng'] ?? null,
            ]);
    }

    public function imageUpdate(int $customerId, array $data): bool
    {
        $sql = "UPDATE customer SET image = :image, updated_at = NOW() WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'id' => $customerId,
            'image' => $data['image']
        ]);
    }

    public function update(int $id, array $data):bool{
        $fields = [];
        $params = [
            'id' => $id
        ];

        foreach(["email", "name", "address", "phone", "location", "lat_lng"] as $col){
            if(isset($data[$col])){
                $fields[] = "$col = :$col";
                //"email = : email"
                //"name = : name"
                $params[$col] = $data[$col];
                
                //"email" => $data['email']
                //"name" => $data['name]
            }
        }

        $sql = "UPDATE customer SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function delete(int $customerId):bool{
        $sql = "DELETE FROM customer WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['id' => $customerId]);
    }
}