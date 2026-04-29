<?php

declare(strict_types=1);

namespace Modules\Setting\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class SettingValueCast implements CastsAttributes
{
    /**
     * Cast the setting's value from the database to its appropriate PHP type.
     *
     * @param array<string, mixed> $attributes
     *
     * @return mixed The casted value (e.g., bool, int, float, array, string, or null).
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        // If the database value itself is null, always return null.
        if (is_null($value)) {
            return null;
        }

        $type = $attributes['type'] ?? 'string';

        return match ($type) {
            'json', 'array' => json_decode($value, true),
            'boolean' => (bool) $value,
            'integer' => (int) $value,
            'float' => (float) $value,
            'null' => null, // Handle the case where the type is explicitly 'null'.
            default => $value, // Handles 'string'
        };
    }

    /**
     * Prepare the given value for storage, determining its type and serializing if necessary.
     *
     * This method inspects the incoming value's PHP type, determines the corresponding
     * storage type (e.g., 'json', 'boolean', 'integer', 'null'), and serializes the
     * value for database storage. It returns an array containing both the serialized
     * 'value' and the detected 'type' to be updated on the model.
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
            'double' => 'float', // Map PHP 'double' to our 'float' type.
            'NULL' => 'null', // Handle null values.
            default => 'string',
        };

        $storableValue = match ($dbType) {
            'json' => json_encode($value),
            'boolean' => (int) $value, // Store booleans as 0 or 1.
            'null' => null, // Store actual null in the database.
            default => (string) $value, // Cast everything else to a string.
        };

        return [
            'value' => $storableValue,
            'type' => $dbType,
        ];
    }
}
