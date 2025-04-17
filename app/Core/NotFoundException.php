<?php

namespace App\Core;

use Exception;

class NotFoundException extends Exception{

    //This special method name will be automatically called with an instance of this class is created
    public function __construct($message = "Resource not found", $code = 404, $previous = null)
    {
        parent::__construct($message, $code, $previous);
        //Parent here refers to the exception
    }   
}