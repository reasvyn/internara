<?php

declare(strict_types=1);

namespace App\Domain\Program\Aggregates\InternshipGroup\Entities;

use App\Domain\Core\Entities\BaseEntity;
use Illuminate\Database\Eloquent\Model;

final readonly class InternshipGroupState extends BaseEntity
{
    public function __construct(
        private int $memberCount,
        private bool $isActive,
    ) {}

    public static function fromModel(Model $model): static
    {
        return new self(
            memberCount: (int) ($model->relationLoaded('members') ? $model->members->count() : $model->members()->count()),
            isActive: (bool) ($model->is_active ?? false),
        );
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function hasMembers(): bool
    {
        return $this->memberCount > 0;
    }

    public function canBeDeleted(): bool
    {
        return ! $this->hasMembers();
    }
}
