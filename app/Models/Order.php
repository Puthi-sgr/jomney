<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class Order{
    private PDO $db;

    public function __construct(){
        //Get a PDO connect from a database wrapper
        $this->db = (new Database())->getConnection();
    }

    public function create(int $userId, array $menuItems, float $totalPrice):bool{
       $sql = "INSERT INTO orders (user_id, items, total) 
                VALUES (:user_id, :items, :total)";

        $stmt = $this->db->prepare($sql);//Prepare the above sql statement
        $result = $stmt->execute([
            'user_id' => $userId,
            'items' => json_encode($menuItems), //convert php array into json string
            'total' => $totalPrice
        ]);

        return $result;
    }

    //Get all orders from a particular user
    public function getByUser(int $userId):array {

        $sql  = "SELECT id, items, total, status, created_at
                 FROM orders
                 WHERE user_id = :user_id
                 ORDER BY created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['user_id' => $userId]);
        //rows of data is now stored in the stmt object
            //buffer pointers is point in the first row
            
        $orders = $stmt->fetchAll(); 
        //Let buffer pointer point to every row to grab all the data.

        return $orders;
    }
}