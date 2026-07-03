<?php

declare(strict_types=1);

namespace App\Settings\Support;

use App\Settings\Enums\SettingType;

final class SettingCaster
{
    public static function cast(mixed $value, SettingType $type): mixed
    {
        return match ($type) {
            SettingType::BOOLEAN => self::castBoolean($value),
            SettingType::INTEGER => (int) $value,
            SettingType::FLOAT => (float) $value,
            SettingType::JSON => self::castJson($value),
            SettingType::NULL => null,
            default => (string) $value,
        };
    }

    private static function castBoolean(mixed $value): bool
    {
        return match (true) {
            in_array($value, [true, 1, '1', 'true'], true) => true,
            in_array($value, [false, 0, '0', 'false', null], true) => false,
            default => (bool) $value,
        };
    }

    private static function castJson(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (! is_string($value)) {
            return [];
        }

        $decoded = json_decode($value, true);

        return $decoded === null && json_last_error() !== JSON_ERROR_NONE ? [] : ($decoded ?? []);
    }
}
