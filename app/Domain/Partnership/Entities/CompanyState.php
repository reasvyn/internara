<?php

declare(strict_types=1);

namespace App\Domain\Partnership\Entities;

use App\Domain\Core\Entities\BaseEntity;
use Illuminate\Database\Eloquent\Model;

final readonly class CompanyState extends BaseEntity
{
    public function __construct(
        private int $placementCount,
        private int $partnershipCount,
    ) {}

    public static function fromModel(Model $model): static
    {
        return new self(
            placementCount: (int) ($model->placements_count ?? 0),
            partnershipCount: (int) ($model->partnerships_count ?? 0),
        );
    }

    public function canBeDeleted(): bool
    {
        return $this->placementCount === 0 && $this->partnershipCount === 0;
    }
}
