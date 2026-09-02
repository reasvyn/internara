<?php

declare(strict_types=1);

namespace App\Modules\Settings\Enums;

use App\Modules\Core\Contracts\LabelEnum;
use App\Modules\Settings\Support\SettingCaster;

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
            self::STRING => __('setting.types.string'),
            self::INTEGER => __('setting.types.integer'),
            self::FLOAT => __('setting.types.float'),
            self::BOOLEAN => __('setting.types.boolean'),
            self::JSON => __('setting.types.json'),
            self::ENCRYPTED => __('setting.types.encrypted'),
            self::NULL => __('setting.types.null'),
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
