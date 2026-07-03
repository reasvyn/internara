<?php

declare(strict_types=1);

namespace App\Settings\Enums;

use App\Core\Contracts\LabelEnum;
use App\Settings\Support\SettingCaster;

enum SettingType: string implements LabelEnum
{
    case STRING = 'string';
    case INTEGER = 'integer';
    case FLOAT = 'float';
    case BOOLEAN = 'boolean';
    case JSON = 'json';
    case ENCRYPTED = 'encrypted';
    case NULL = 'null';

    public function label(): string
    {
        return match ($this) {
            self::STRING => __('settings.types.string'),
            self::INTEGER => __('settings.types.integer'),
            self::FLOAT => __('settings.types.float'),
            self::BOOLEAN => __('settings.types.boolean'),
            self::JSON => __('settings.types.json'),
            self::ENCRYPTED => __('settings.types.encrypted'),
            self::NULL => __('settings.types.null'),
        };
    }

    public static function detect(mixed $value): self
    {
        return match (true) {
            is_bool($value) => self::BOOLEAN,
            is_int($value) => self::INTEGER,
            is_float($value) => self::FLOAT,
            is_array($value) => self::JSON,
            $value === null => self::NULL,
            default => self::STRING,
        };
    }

    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }

    public function cast(mixed $value): mixed
    {
        return SettingCaster::cast($value, $this);
    }
}
