<?php

declare(strict_types=1);

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Casts setting values between database storage and typed PHP values.
 *
 * S2 - Sustain: Values are stored and retrieved with their correct PHP types,
 * eliminating manual casting throughout the codebase.
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
}
