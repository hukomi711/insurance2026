<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

/**
 * Safely encrypts values at rest.
 *
 * On read:  tries to decrypt; if the stored value is still plaintext
 *           (not yet migrated), returns it as-is without throwing.
 * On write: always encrypts via Laravel's Encrypter.
 *
 * This allows a gradual migration from plaintext → encrypted columns
 * without breaking existing rows.
 */
class EncryptedSafe implements CastsAttributes
{
    /**
     * Decrypt the value when reading from the database.
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Illuminate\Contracts\Encryption\DecryptException) {
            // Value is still plaintext (not yet encrypted) — return as-is
            return $value;
        }
    }

    /**
     * Encrypt the value when writing to the database.
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        return Crypt::encryptString($value);
    }
}
