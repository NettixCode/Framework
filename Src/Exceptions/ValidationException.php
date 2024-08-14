<?php

namespace Nettixcode\Framework\Exceptions;

use Exception;

class ValidationException extends Exception
{
    protected $errors;

    public function __construct($errors)
    {
        parent::__construct("Validation failed");
        $this->errors = $errors;
    }

    public function getErrors()
    {
        return $this->errors;
    }
}
