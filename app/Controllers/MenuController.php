<?php

namespace App\Controllers;
use App\Traits\ValidationTrait;


class MenuController{
    use ValidationTrait;
    public function index(){
       	// session_start();
        throw new \Exception("Erorr shitt");
        echo"Welcome to menu";
    }

    public function create(){
        echo "Add new item(coming soon)";
    }

    public function store(){
        $name = $_POST['name'] ?? '';
        $price = $_POST['price'] ?? '';

        if(!$this->validateText($name)){
            http_response_code(422);
            echo "❌ Invalid name";
            return;
        }
        $price =$this->sanitizeText($price);

        if(!$this->validateInt($price)){
            http_response_code(422);
            echo "❌ Invalid price $price";
            return;
        }

        //update the variables after the validating it
        $name = $this->sanitizeText($name);
        $price = (int) $price;
       
        echo "✅ Validated item: $name ($price)";
    }

}