<?php

namespace App\Exceptions;

use Exception;

class CompteNotFoundException extends Exception
{
    public function __construct(string $numero = null)
    {
        $message = $numero
            ? "Le compte numéro {$numero} n'a pas été trouvé."
            : "Le compte demandé n'a pas été trouvé.";

        parent::__construct($message, 404);
    }
}