<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class StrongPassword implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (strlen($value) < 8) {
            $fail("O campo {$attribute} deve ter pelo menos 8 caracteres.");
            return;
        }

        if (!preg_match('/[A-Z]/', $value)) {
            $fail("O campo {$attribute} deve conter pelo menos uma letra maiúscula.");
            return;
        }

        if (!preg_match('/[a-z]/', $value)) {
            $fail("O campo {$attribute} deve conter pelo menos uma letra minúscula.");
            return;
        }

        if (!preg_match('/[0-9]/', $value)) {
            $fail("O campo {$attribute} deve conter pelo menos um número.");
        }
    }
}
