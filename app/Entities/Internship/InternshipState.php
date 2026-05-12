<?php

declare(strict_types=1);

namespace App\Entities\Internship;

use App\Entities\BaseEntity;
use Illuminate\Database\Eloquent\Model;

final readonly class InternshipState extends BaseEntity
{
    public function __construct(
        private int $placementCount,
        private int $registrationCount,
    ) {}

    public static function fromModel(Model $model): static
    {
        return new self(
            placementCount: (int) ($model->placements_count ?? $model->placements()->count()),
            registrationCount: (int) ($model->registrations_count ?? $model->registrations()->count()),
        );
    }

    public function canBeDeleted(): bool
    {
        return $this->placementCount === 0 && $this->registrationCount === 0;
    }
}
