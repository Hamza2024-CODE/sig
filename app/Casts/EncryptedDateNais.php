<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;

/**
 * Cast: EncryptedDateNais
 * Encrypts DateNais automatically on save, and decrypts on retrieve.
 * Supports legacy plain-text dates during transition.
 */
class EncryptedDateNais implements CastsAttributes
{
    public function get($model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        try {
            return Crypt::decryptString($value);
        } catch (DecryptException) {
            return $value; // plain text fallback
        }
    }

    public function set($model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        try {
            Crypt::decryptString($value);
            return $value; // already encrypted
        } catch (DecryptException) {
            return Crypt::encryptString($value); // encrypt it
        }
    }
}