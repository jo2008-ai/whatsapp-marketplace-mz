<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SafeString implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Prevenir XSS básico
        $dangerous = ['<script', 'javascript:', 'onerror=', 'onload=', 'onclick='];
        $lower = strtolower($value);

        foreach ($dangerous as $pattern) {
            if (str_contains($lower, $pattern)) {
                $fail("O campo {$attribute} contém caracteres não permitidos.");
                return;
            }
        }

        // Prevenir SQL injection básico (defense in depth, Eloquent já protege)
        $sqlPatterns = ["'", '"', ';--', '/*', '*/', 'UNION SELECT', 'DROP TABLE'];
        foreach ($sqlPatterns as $pattern) {
            if (str_contains(strtoupper($value), strtoupper($pattern))) {
                $fail("O campo {$attribute} contém caracteres não permitidos.");
                return;
            }
        }
    }
}
