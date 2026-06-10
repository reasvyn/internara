<?php

declare(strict_types=1);

namespace App\Settings\Entities;

use App\Core\Entities\BaseEntity;
use App\Settings\Enums\SettingType;
use Illuminate\Database\Eloquent\Model;

final readonly class SettingEntity extends BaseEntity
{
    public function __construct(
        public string $key,
        public mixed $value,
        public ?string $type,
        public ?string $group,
    ) {}

    public static function fromModel(Model $model): static
    {
        return new self(
            key: $model->key,
            value: $model->value,
            type: $model->type ?? null,
            group: $model->group ?? null,
        );
    }

    public function settingType(): ?SettingType
    {
        return $this->type !== null ? SettingType::tryFrom($this->type) : null;
    }

    public function isType(SettingType $type): bool
    {
        return $this->type === $type->value;
    }

    public function isBoolean(): bool
    {
        return $this->isType(SettingType::BOOLEAN);
    }

    public function booleanValue(): bool
    {
        return (bool) $this->value;
    }

    public function isJson(): bool
    {
        return $this->isType(SettingType::JSON);
    }

    public function jsonValue(): array
    {
        return is_array($this->value) ? $this->value : [];
    }

    public function isEncrypted(): bool
    {
        return $this->isType(SettingType::ENCRYPTED);
    }

    public function isString(): bool
    {
        return $this->isType(SettingType::STRING);
    }

    public function isInteger(): bool
    {
        return $this->isType(SettingType::INTEGER);
    }

    public function intValue(): int
    {
        return (int) $this->value;
    }

    public function isFloat(): bool
    {
        return $this->isType(SettingType::FLOAT);
    }

    public function floatValue(): float
    {
        return (float) $this->value;
    }

    public function isEmpty(): bool
    {
        return $this->value === null || $this->value === '';
    }

    public function isThemeColor(): bool
    {
        return in_array($this->key, config('settings.theme_cache_keys', []), true);
    }

    public function belongsToGroup(string $group): bool
    {
        return $this->group === $group;
    }
}
