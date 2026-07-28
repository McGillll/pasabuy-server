<?php

namespace App\Exceptions;

use RuntimeException;

class AuthenticationFailedException extends RuntimeException
{
    public function __construct(string $message, protected ?array $errors = null)
    {
        parent::__construct($message);
    }

    public function getErrors()
    {
        return $this->errors;
    }
}
