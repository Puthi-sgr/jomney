<?php

namespace App\Controllers;

use App\Models\User;

class AuthController{
    private User $userModel;

    public function __construct()
    {   
        $this->userModel = new User();
        session_start();
    }

    public function register(){
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        if(!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 6){
            http_response_code(422);
            echo "Invalid email or password.";
            return;
        }

        $isEmailExisted = $this->userModel->findByEmail($email);
        if($isEmailExisted){
            http_response_code(409);
            echo "Email is already registered";
            return;
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $result = $this->userModel->create($email, $hashedPassword);

        if($result){
            echo "Account has been successfully created";
        }else{
            http_response_code(404);
            echo "Registration failed";
        }
    }

    public function login(){
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        $user = $this->userModel->findByEmail($email);
        if(!$user || !password_verify($password, $user['password'])){
            http_response_code(401);
            echo "Invalid credentials";
            return;
        }

        $_SESSION['user_id'] = $user['id'];
        echo "Login successfully";
    }

    public function logout(){
        session_destroy();
        echo "Logged out successfully";
    }
}