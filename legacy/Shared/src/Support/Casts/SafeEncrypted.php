<?php

declare(strict_types=1);

namespace Modules\Shared\Support\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

/**
 * Class SafeEncrypted
 *
 * Provides a secure encryption cast that gracefully handles DecryptException.
 * If the MAC is invalid (e.g. after an APP_KEY change), it returns the raw value
 * instead of crashing the application, allowing for recovery or re-saving.
 */
class SafeEncrypted implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param Model $model
     * @param mixed $value
     *
     * @return mixed
     */
    public function get($model, string $key, $value, array $attributes)
    {
        if (is_null($value)) {
            return null;
        }

        try {
            return Crypt::decryptString((string) $value);
        } catch (DecryptException $e) {
            // [S1 - Secure] Log the failure but don't expose sensitive raw data
            Log::error(
                "SafeEncrypted: Decryption failed for attribute [{$key}] on model [".
                    get_class($model).
                    ']. The APP_KEY might have changed or data is tampered.',
            );

            // Return raw value or null to prevent crash.
            // In a well-behaved system, this allows the UI to still function.
            return $value;
        }
    }

    /**
     * Prepare the given value for storage.
     *
     * @param Model $model
     * @param mixed $value
     *
     * @return mixed
     */
    public function set($model, string $key, $value, array $attributes)
    {
        return is_null($value) ? null : Crypt::encryptString((string) $value);
    }
}
