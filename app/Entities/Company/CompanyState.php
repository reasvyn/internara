<?php

declare(strict_types=1);

namespace App\Entities\Company;

use App\Entities\BaseEntity;
use Illuminate\Database\Eloquent\Model;

final readonly class CompanyState extends BaseEntity
{
    public function __construct(
        private int $placementCount,
    ) {}

    public static function fromModel(Model $model): static
    {
        return new self(
            placementCount: (int) ($model->placements_count ?? $model->placements()->count()),
        );
    }

    public function canBeDeleted(): bool
    {
        return $this->placementCount === 0;
    }
}
