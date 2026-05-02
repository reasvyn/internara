<?php

declare(strict_types=1);

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

/**
 * Casts setting values between database storage and typed PHP values.
 * Now supports 'encrypted' type for sensitive data like SMTP passwords.
 *
 * S1 - Secure: Sensitive strings are transparently encrypted at rest.
 * S2 - Sustain: Values are stored and retrieved with their correct PHP types.
 */
class SettingValueCast implements CastsAttributes
{
    /**
     * Cast the stored value to its appropriate PHP type.
     *
     * @param array<string, mixed> $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if (is_null($value)) {
            return null;
        }

        $type = $attributes['type'] ?? 'string';

        return match ($type) {
            'json', 'array' => json_decode($value, true),
            'boolean' => (bool) $value,
            'integer' => (int) $value,
            'float' => (float) $value,
            'encrypted' => $this->decrypt($value),
            'null' => null,
            default => $value,
        };
    }

    /**
     * Prepare the value for storage, detecting type from PHP type.
     *
     * @param array<string, mixed> $attributes
     *
     * @return array{'value': string|null, 'type': string}
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): array
    {
        // If type is already set as encrypted (manual override)
        $targetType = $attributes['type'] ?? null;

        if ($targetType === 'encrypted') {
            return [
                'value' => Crypt::encryptString((string) $value),
                'type' => 'encrypted',
            ];
        }

        $phpType = gettype($value);

        $dbType = match ($phpType) {
            'array', 'object' => 'json',
            'boolean' => 'boolean',
            'integer' => 'integer',
            'double' => 'float',
            'NULL' => 'null',
            default => 'string',
        };

        $storableValue = match ($dbType) {
            'json' => json_encode($value),
            'boolean' => (int) $value,
            'null' => null,
            default => (string) $value,
        };

        return [
            'value' => $storableValue,
            'type' => $dbType,
        ];
    }

    /**
     * Decrypt a value, returning original if decryption fails.
     */
    private function decrypt(string $value): string
    {
        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            // Fallback for legacy plaintext data
            return $value;
        }
    }
}
