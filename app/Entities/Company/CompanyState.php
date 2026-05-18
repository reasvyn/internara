<?php

declare(strict_types=1);

namespace App\Entities\Company;

use App\Entities\BaseEntity;
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
            placementCount: (int) ($model->placements_count ?? $model->placements()->count()),
            partnershipCount: (int) ($model->partnerships_count ?? $model->partnerships()->count()),
        );
    }

    public function canBeDeleted(): bool
    {
        return $this->placementCount === 0 && $this->partnershipCount === 0;
    }
}
