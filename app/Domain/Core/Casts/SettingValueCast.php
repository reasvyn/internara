<?php

declare(strict_types=1);

namespace App\Domain\Core\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Casts setting values between database storage and typed PHP values.
 * Supports 'encrypted' type for sensitive data like SMTP passwords.
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
            'json', 'array' => $this->decodeJson($value, $model, $key),
            'boolean' => (bool) $value,
            'integer' => (int) $value,
            'float' => (float) $value,
            'encrypted' => $this->decrypt($value, $model, $key),
            'null' => null,
            default => $value,
        };
    }

    /**
     * Prepare the value for storage, detecting type from PHP type.
     *
     * @param array<string, mixed> $attributes
     *
     * @return array{value: string|null, type: string}
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): array
    {
        $targetType = $attributes['type'] ?? null;

        if ($targetType === 'encrypted') {
            return [
                'value' => $this->encrypt((string) $value, $model, $key),
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
            'json' => $this->encodeJson($value, $model, $key),
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
     * Decrypt a value with logging on failure.
     */
    private function decrypt(string $value, Model $model, string $key): string
    {
        try {
            return Crypt::decryptString($value);
        } catch (DecryptException $e) {
            Log::error('Failed to decrypt setting value', [
                'model' => $model::class,
                'key' => $key,
                'setting_id' => $model->getKey(),
                'error' => $e->getMessage(),
            ]);

            return $value;
        }
    }

    /**
     * Encrypt a value with logging on failure.
     */
    private function encrypt(string $value, Model $model, string $key): string
    {
        try {
            return Crypt::encryptString($value);
        } catch (\Throwable $e) {
            Log::error('Failed to encrypt setting value', [
                'model' => $model::class,
                'key' => $key,
                'setting_id' => $model->getKey(),
                'error' => $e->getMessage(),
            ]);

            throw new RuntimeException('Failed to encrypt setting value.', previous: $e);
        }
    }

    /**
     * Decode JSON with error handling.
     */
    private function decodeJson(string $value, Model $model, string $key): mixed
    {
        $decoded = json_decode($value, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('Invalid JSON in setting value', [
                'model' => $model::class,
                'key' => $key,
                'setting_id' => $model->getKey(),
                'json_error' => json_last_error_msg(),
            ]);

            return [];
        }

        return $decoded;
    }

    /**
     * Encode JSON with error handling.
     */
    private function encodeJson(mixed $value, Model $model, string $key): string
    {
        $encoded = json_encode($value);

        if ($encoded === false) {
            Log::error('Failed to encode setting value as JSON', [
                'model' => $model::class,
                'key' => $key,
                'setting_id' => $model->getKey(),
                'json_error' => json_last_error_msg(),
            ]);

            throw new RuntimeException('Failed to encode setting value as JSON.', previous: new RuntimeException(json_last_error_msg()));
        }

        return $encoded;
    }
}
