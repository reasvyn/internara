<?php

declare(strict_types=1);

namespace App\Core\Data;

use Illuminate\Support\Str;

abstract readonly class BaseData
{
    public function toArray(): array
    {
        $result = [];

        foreach (get_object_vars($this) as $key => $value) {
            $result[$key] = $value instanceof self ? $value->toArray() : $value;
        }

        return $result;
    }

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

    public static function from(mixed $source): static
    {
        if (is_array($source)) {
            return static::fromArray($source);
        }

        if (is_object($source) && method_exists($source, 'toArray')) {
            return static::fromArray($source->toArray());
        }

        throw new \InvalidArgumentException('Unsupported source type: '.get_debug_type($source));
    }
}
