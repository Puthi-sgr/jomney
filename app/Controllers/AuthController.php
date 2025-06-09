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

    

    public function register(){
        $data = json_decode(file_get_contents('php://input'), true);

        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';

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
        $rawInput = file_get_contents('php://input');
 
        
        $body = json_decode($rawInput, true);
        $email = $body['email'] ?? '';
        $password = $body['password'] ?? '';


        //Finds the user by email
        $user = $this->userModel->findByEmail($email);

        if(!$user || !password_verify($password, $user['password'])){
            Response::error("Invalid credentials", ["email" => $email, "password" => $password], 401);
        }

        //Generate token
        $token = JWTService::generateToken($user['id']);

        // Before sending the response
        file_put_contents('debug.log', "Response data: " . json_encode([
            'success' => true,
            'message' => "Login successful",
            'data' => ["token" => $token]
        ]) . "\n", FILE_APPEND);

        Response::success("Login successful", ["token" => $token], 200);
    }

    public function logout(){
        session_destroy();
        Response::success("Log successful", [], 200);
    }
}