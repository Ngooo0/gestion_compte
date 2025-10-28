<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SenegalesePhoneRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Formats de téléphone sénégalais acceptés :
        // +221771234567, +221761234567, +221701234567, etc.
        // 771234567, 761234567, 701234567, etc. (sans indicatif)
        // 221771234567, 221761234567, etc.

        $pattern = '/^(?:\+221|221)?(?:77|78|76|70|75|33)\d{7}$/';

        if (!preg_match($pattern, $value)) {
            $fail('Le numéro de téléphone doit être un numéro sénégalais valide (ex: +221771234567 ou 771234567).');
        }
    }
}
