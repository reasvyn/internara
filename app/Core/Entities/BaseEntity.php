<?php

declare(strict_types=1);

namespace App\Core\Entities;

use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use JsonSerializable;

abstract readonly class BaseEntity implements JsonSerializable
{
    abstract public static function fromModel(Model $model): static;

    public static function fromArray(array $data): static
    {
        $constructorParams = [];

        $ref = new \ReflectionClass(static::class);
        $constructor = $ref->getConstructor();
        $params = $constructor?->getParameters() ?? [];

        foreach ($params as $param) {
            $name = $param->getName();

            if (array_key_exists($name, $data)) {
                $constructorParams[$name] = $data[$name];
            } elseif ($param->isDefaultValueAvailable()) {
                $constructorParams[$name] = $param->getDefaultValue();
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

    public function toArray(): array
    {
        $data = [];

        foreach (get_object_vars($this) as $key => $value) {
            $data[$key] = match (true) {
                $value instanceof self => $value->toArray(),
                $value instanceof JsonSerializable => $value->jsonSerialize(),
                is_array($value) => array_map(
                    fn (mixed $item) => $item instanceof self ? $item->toArray() : $item,
                    $value,
                ),
                default => $value,
            };
        }

        return $data;
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function equals(self $other): bool
    {
        return $this === $other || $this->toArray() === $other->toArray();
    }

    public function with(string $property, mixed $value): static
    {
        $data = $this->toArray();
        $data[$property] = $value;

        return static::fromArray($data);
    }
}
