<?php

declare(strict_types=1);

namespace App\Core\Data;

use Illuminate\Support\Str;
use InvalidArgumentException;
use JsonSerializable;

abstract readonly class BaseData implements JsonSerializable
{
    public function toArray(): array
    {
        $result = [];

        foreach (get_object_vars($this) as $key => $value) {
            $result[$key] = match (true) {
                $value instanceof self => $value->toArray(),
                $value instanceof JsonSerializable => $value->jsonSerialize(),
                is_array($value) => array_map(
                    fn (mixed $item) => $item instanceof self ? $item->toArray() : $item,
                    $value,
                ),
                default => $value,
            };
        }

        return $result;
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function only(string ...$keys): array
    {
        $data = $this->toArray();

        $result = [];

        foreach ($keys as $key) {
            if (array_key_exists($key, $data)) {
                $result[$key] = $data[$key];
            }
        }

        return $result;
    }

    public function except(string ...$keys): array
    {
        $data = $this->toArray();

        foreach ($keys as $key) {
            unset($data[$key]);
        }

        return $data;
    }

    public function merge(array $overrides): static
    {
        $data = array_merge($this->toArray(), $overrides);

        return static::fromArray($data);
    }

    public static function fromArray(array $data): static
    {
        $params = self::resolveConstructorParams(static::class);

        $constructorParams = [];

        foreach ($params as $name => $defaultValue) {
            $snakeKey = Str::snake($name);

            if (array_key_exists($name, $data)) {
                $constructorParams[$name] = $data[$name];
            } elseif (array_key_exists($snakeKey, $data)) {
                $constructorParams[$name] = $data[$snakeKey];
            } elseif ($defaultValue !== '__REQUIRED__') {
                $constructorParams[$name] = $defaultValue;
            } else {
                throw new InvalidArgumentException(
                    sprintf(
                        'Missing required constructor parameter "%s" for %s',
                        $name,
                        static::class,
                    ),
                );
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

        throw new InvalidArgumentException('Unsupported source type: '.get_debug_type($source));
    }

    private static function resolveConstructorParams(string $class): array
    {
        static $cache = [];
        static $generation = 0;

        if (isset($cache[$class])) {
            return $cache[$class];
        }

        $ref = new \ReflectionClass($class);
        $constructor = $ref->getConstructor();

        if ($constructor === null) {
            $cache[$class] = [];

            return [];
        }

        $params = [];

        foreach ($constructor->getParameters() as $param) {
            $name = $param->getName();

            if ($param->isDefaultValueAvailable()) {
                $params[$name] = $param->getDefaultValue();
            } else {
                $params[$name] = '__REQUIRED__';
            }
        }

        $cache[$class] = $params;

        return $params;
    }

    public static function clearParamCache(): void
    {
        self::$cache = [];
        self::$generation++;
    }
}
