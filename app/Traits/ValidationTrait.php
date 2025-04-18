<?php

namespace App\Traits;

trait ValidationTrait{
    protected function validationEmail(string $email):bool{
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    protected function validateText(string $input, int $min = 2, int $max = 100):bool{
        $length = mb_strlen($input);
        return $length >= $min && $length <= $max;
    }

    protected function validateInt(string $input):bool {
        return ctype_digit($input);
        //validate integer 0-9
    }

    protected function sanitizeText(string $input): string{
        return htmlspecialchars(trim($input), ENT_QUOTES, "UTF-8");
        //Cleans up elements like < /> stuff from html
    }
}

