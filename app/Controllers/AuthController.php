<?php

namespace App\Controllers;

use App\Core\JWTService;
use App\Models\User;
use App\Core\Response;

class AuthController{
    private User $userModel;

    public function __construct()
    {   
        $this->userModel = new User();
        JWTService::init(); //Make sure that the secret and TTl are loaded
    }

    private function debugLog($password, $user):void{
        $newHash = password_hash('secret', PASSWORD_DEFAULT);
        file_put_contents('debug.log', "New hash: $newHash\n", FILE_APPEND);
        file_put_contents('debug.log', "Password from request: $password\n", FILE_APPEND);
        file_put_contents('debug.log', "Hash from DB: {$user['password']}\n", FILE_APPEND);
        file_put_contents('debug.log', "Verification result: " . (password_verify($password, $user['password']) ? 'true' : 'false') . "\n", FILE_APPEND);
    }

    public function register(){
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        if(!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 6){
            Response::error("Invalid email or password",["Extra message" => "Dummy"], 422);
            return;
        }

        $isEmailExisted = $this->userModel->findByEmail($email);
        if($isEmailExisted){

            Response::error("Email has already been taken", [], 409 );
            return;
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $result = $this->userModel->create($email, $hashedPassword);

        if($result){
           Response::success("Registration successful", ["email" => $email, "encrypted_password" => $hashedPassword], 200);
        }else{
            Response::error("Registration failed", [], 201);
        }
    }

    public function login(){
        $body = json_decode(file_get_contents('php://input'), true);
        $email = $body['email'] ?? '';
        $password = $body['password'] ?? '';

        //Finds the user by email
        $user = $this->userModel->findByEmail($email);
       
        $this->debugLog($password, $user);

        if(!$user || !password_verify($password, $user['password'])){
            Response::error("Invalid credentials", [], 401);
        }

        //Generate token
        $token = JWTService::generateToken($user['id']);
        Response::success("Login successful", ["token" => $token], 200);
    }

    public function logout(){
        session_destroy();
        Response::success("Log successful", [], 200);
    }
}