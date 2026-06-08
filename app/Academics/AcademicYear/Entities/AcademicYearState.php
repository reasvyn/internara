<?php

declare(strict_types=1);

namespace App\Academics\AcademicYear\Entities;

use App\Core\Entities\BaseEntity;
use Illuminate\Database\Eloquent\Model;
use Throwable;

final readonly class AcademicYearState extends BaseEntity
{
    public function __construct(private bool $isActive, private bool $hasRelatedRecords = false) {}

    public static function fromModel(Model $model): static
    {
        try {
            $hasInternships = $model->relationLoaded('internships')
                ? $model->internships->isNotEmpty()
                : $model->internships()->exists();
        } catch (Throwable) {
            $hasInternships = false;
        }

        try {
            $hasAssessments = $model->relationLoaded('assessments')
                ? $model->assessments->isNotEmpty()
                : $model->assessments()->exists();
        } catch (Throwable) {
            $hasAssessments = false;
        }

        return new self(
            isActive: (bool) ($model->is_active ?? false),
            hasRelatedRecords: $hasInternships || $hasAssessments,
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
