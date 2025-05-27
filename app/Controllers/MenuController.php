<?php

namespace App\Controllers;
use App\Traits\ValidationTrait;
use App\Models\MenuItem;
use App\Core\Response;
class MenuController{

    use ValidationTrait;

    //initialize the model
    private MenuItem $menuModel;

    public function __construct(){
        //instantiate the model
        $this->menuModel = new MenuItem();
    }

    public function index(){
        
        $items = $this->menuModel->all();

        Response::success("Menu items retrieved", $items);
    }

    public function create(){
        //1 decode
        $body = json_decode(file_get_contents('php://inputs'), true);

        $name = $body['name' ] ?? '';
        $description = $body['description'] ?? '';
        $price = $body['price'] ?? null;

        //2 checks
        if(!$this->validateText($name)){
            Response::error('Invalid name', [], 422);
            return;
        }
        if(!$this->validateText($description)){
            Response::error('Invalid description', [], 422);
            return;
        }
        if(!$this->validateInt($price)){
            Response::error('Invalid price', [], 422);
            return;
        }

        //3 sanitize
        $name = $this->sanitizeText($name);
        $description = $this->sanitizeText($description);
        $price = (float) $price;
        
        //4 create  
        $isSuccess = $this->menuModel->create($name, $description, $price);
        //5 response
        if($isSuccess){
            Response::success("Menu item created", [], 201);
        }else{
            Response::error("Failed to create item", [], 500);
        }
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