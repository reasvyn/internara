<?php

declare(strict_types=1);

namespace App\Core\Data;

use Illuminate\Support\Str;

abstract readonly class BaseData
{
    /**
     * Convert the DTO to an array.
     * By default, extracts all public properties recursively.
     */
    public function toArray(): array
    {
        $result = [];

        foreach (get_object_vars($this) as $key => $value) {
            $result[$key] = $value instanceof self ? $value->toArray() : $value;
        }

        return $result;
    }

    /**
     * Create a new instance from an array of data.
     * Uses named constructor pattern — override in subclasses for custom hydration.
     */
    public static function fromArray(array $data): static
    {
        $constructorParams = [];

        $ref = new \ReflectionClass(static::class);
        $params = $ref->getConstructor()?->getParameters() ?? [];

        foreach ($params as $param) {
            $name = $param->getName();
            $snakeKey = Str::snake($name);

            if (array_key_exists($name, $data)) {
                $constructorParams[$name] = $data[$name];
            } elseif (array_key_exists($snakeKey, $data)) {
                $constructorParams[$name] = $data[$snakeKey];
            } elseif ($param->isDefaultValueAvailable()) {
                $constructorParams[$name] = $param->getDefaultValue();
            } else {
                throw new \InvalidArgumentException(sprintf(
                    'Missing required constructor parameter "%s" for %s',
                    $name,
                    static::class,
                ));
            }
        }

        return new static(...$constructorParams);
    }

    /**
     * Create a Data DTO from the current object.
     * Override in subclasses for custom transformation logic.
     */
    public static function from(mixed $source): static
    {
        if (is_array($source)) {
            return static::fromArray($source);
        }

        throw new \InvalidArgumentException('Unsupported source type: '.get_debug_type($source));
    }
}
