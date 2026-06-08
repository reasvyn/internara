<?php

declare(strict_types=1);

namespace App\Settings\Entities;

use App\Core\Entities\BaseEntity;
use App\Settings\Models\Setting;
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

    public function isBoolean(): bool
    {
        return $this->type === 'boolean';
    }

    public function booleanValue(): bool
    {
        return (bool) $this->value;
    }

    public function isJson(): bool
    {
        return $this->type === 'json';
    }

    public function jsonValue(): array
    {
        return is_array($this->value) ? $this->value : [];
    }

    public function isEncrypted(): bool
    {
        return $this->type === 'encrypted';
    }

    public function isEmpty(): bool
    {
        return $this->value === null || $this->value === '';
    }
}