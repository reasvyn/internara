<?php

declare(strict_types=1);

namespace App\Settings\Casts;

use App\Core\Support\SmartLogger;
use App\Settings\Enums\SettingType;
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

        $type = SettingType::tryFrom($attributes['type'] ?? 'string') ?? SettingType::STRING;

        return match ($type) {
            SettingType::JSON => $this->decodeJson($value, $model, $key),
            SettingType::BOOLEAN => (bool) $value,
            SettingType::INTEGER => (int) $value,
            SettingType::FLOAT => (float) $value,
            SettingType::ENCRYPTED => $this->decrypt($value, $model, $key),
            SettingType::NULL => null,
            default => $value,
        };
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): array
    {
        $targetType = isset($attributes['type'])
            ? SettingType::tryFrom($attributes['type'])
            : null;

        if ($targetType === SettingType::ENCRYPTED) {
            if ($value === null) {
                return ['value' => null, 'type' => SettingType::NULL->value];
            }

            return [
                'value' => $this->encrypt((string) $value, $model, $key),
                'type' => SettingType::ENCRYPTED->value,
            ];
        }

        $detectedType = SettingType::detect($value);

        $storableValue = match ($detectedType) {
            SettingType::JSON => $this->encodeJson($value, $model, $key),
            SettingType::BOOLEAN => (int) $value,
            SettingType::NULL => null,
            default => (string) $value,
        };

        return [
            'value' => $storableValue,
            'type' => $detectedType->value,
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
                ->withPiiMasking()
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
                ->withPiiMasking()
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
                ->withPiiMasking()
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
                ->withPiiMasking()
                ->systemOnly()
                ->save();

            throw new RuntimeException(
                'Failed to encode setting value as JSON.',
                previous: new RuntimeException(json_last_error_msg()),
            );
        }

        return $encoded;
    }
}
