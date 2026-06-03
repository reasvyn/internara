<?php

declare(strict_types=1);

namespace App\Domain\Settings\Aggregates\Setting\Casts;

use App\Domain\Core\Support\SmartLogger;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use RuntimeException;

class SettingValueCast implements CastsAttributes
{
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

    private function decrypt(string $value, Model $model, string $key): string
    {
        try {
            return Crypt::decryptString($value);
        } catch (DecryptException $e) {
            SmartLogger::error('Failed to decrypt setting value: '.$e->getMessage())
                ->withPayload([
                    'model' => $model::class,
                    'key' => $key,
                    'setting_id' => $model->getKey(),
                ])
                ->systemOnly()
                ->save();

            return $value;
        }
    }

    private function encrypt(string $value, Model $model, string $key): string
    {
        try {
            return Crypt::encryptString($value);
        } catch (\Throwable $e) {
            SmartLogger::error('Failed to encrypt setting value: '.$e->getMessage())
                ->withPayload([
                    'model' => $model::class,
                    'key' => $key,
                    'setting_id' => $model->getKey(),
                ])
                ->systemOnly()
                ->save();

            throw new RuntimeException('Failed to encrypt setting value.', previous: $e);
        }
    }

    private function decodeJson(string $value, Model $model, string $key): mixed
    {
        $decoded = json_decode($value, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            SmartLogger::error('Invalid JSON in setting value')
                ->withPayload([
                    'model' => $model::class,
                    'key' => $key,
                    'setting_id' => $model->getKey(),
                    'json_error' => json_last_error_msg(),
                ])
                ->systemOnly()
                ->save();

            return [];
        }

        return $decoded;
    }

    private function encodeJson(mixed $value, Model $model, string $key): string
    {
        $encoded = json_encode($value);

        if ($encoded === false) {
            SmartLogger::error('Failed to encode setting value as JSON')
                ->withPayload([
                    'model' => $model::class,
                    'key' => $key,
                    'setting_id' => $model->getKey(),
                    'json_error' => json_last_error_msg(),
                ])
                ->systemOnly()
                ->save();

            throw new RuntimeException('Failed to encode setting value as JSON.', previous: new RuntimeException(json_last_error_msg()));
        }

        return $encoded;
    }
}
