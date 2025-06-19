<?php

namespace App\Exceptions;

use Exception;

class OutOfStockException extends Exception
{
    public function __construct($message = "Item is out of stock", $code = 0, $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
