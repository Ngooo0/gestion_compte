<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SenegaleseNciRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Format NCI sénégalais : 13 chiffres commençant par 1 ou 2
        // Exemples valides : 1980123456789, 2123456789012

        if (!preg_match('/^[12]\d{12}$/', $value)) {
            $fail('Le numéro NCI doit être composé de 13 chiffres et commencer par 1 ou 2.');
        }

        // Vérification de la validité basique du NCI (longueur et format)
        if (strlen($value) !== 13) {
            $fail('Le numéro NCI doit contenir exactement 13 chiffres.');
        }

        // Le premier chiffre doit être 1 (naissance) ou 2 (naturalisation)
        if (!in_array(substr($value, 0, 1), ['1', '2'])) {
            $fail('Le numéro NCI doit commencer par 1 (naissance) ou 2 (naturalisation).');
        }
    }
}
