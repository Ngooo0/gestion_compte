<?php

namespace App\Exceptions;

use Exception;

class UnauthorizedAccessException extends Exception
{
    public function __construct(string $message = "Accès non autorisé à cette ressource.")
    {
        parent::__construct($message, 403);
    }
}