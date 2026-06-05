<?php

declare(strict_types=1);

namespace App\Enrollment\Entities;

use App\Core\Entities\BaseEntity;
use Illuminate\Database\Eloquent\Model;

final readonly class PlacementState extends BaseEntity
{
    public function __construct(
        private int $registrationCount,
    ) {}

    public static function fromModel(Model $model): static
    {
        return new self(
            registrationCount: (int) ($model->registrations_count ?? 0),
        );
    }

    public function canBeDeleted(): bool
    {
        return $this->registrationCount === 0;
    }
}
