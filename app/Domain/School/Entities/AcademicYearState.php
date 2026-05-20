<?php

declare(strict_types=1);

namespace App\Domain\School\Entities;

use App\Domain\Core\Entities\BaseEntity;
use Illuminate\Database\Eloquent\Model;

final readonly class AcademicYearState extends BaseEntity
{
    public function __construct(
        private bool $isActive,
        private bool $hasRelatedRecords = false,
    ) {}

    public static function fromModel(Model $model): static
    {
        return new self(
            isActive: (bool) ($model->is_active ?? false),
            hasRelatedRecords: $model->relationLoaded('internships')
                ? $model->internships->isNotEmpty()
                : $model->internships()->exists(),
        );
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function hasRelatedRecords(): bool
    {
        return $this->hasRelatedRecords;
    }

    public function canBeActivated(): bool
    {
        return ! $this->isActive;
    }

    public function canBeDeleted(): bool
    {
        return ! $this->isActive && ! $this->hasRelatedRecords;
    }
}
