<?php

declare(strict_types=1);

namespace App\Domain\Program\Aggregates\Internship\Entities;

use App\Domain\Core\Entities\BaseEntity;
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
            placementCount: (int) ($model->placements_count ?? 0),
            registrationCount: (int) ($model->registrations_count ?? 0),
        );
    }

    public function canBeDeleted(): bool
    {
        return $this->placementCount === 0 && $this->registrationCount === 0;
    }
}
