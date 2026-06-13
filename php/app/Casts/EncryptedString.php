<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Support\Facades\Crypt;

class EncryptedString implements CastsAttributes
{
    public function get($model, string $key, $value, array $attributes): ?string
    {
        if (!$value) return null;

        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            return $value; // Dados legados sem encriptação
        }
    }

    public function set($model, string $key, $value, array $attributes): ?string
    {
        if (!$value) return null;

        return Crypt::encryptString($value);
    }
}
