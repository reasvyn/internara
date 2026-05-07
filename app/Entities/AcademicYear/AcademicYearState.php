<?php

declare(strict_types=1);

namespace App\Entities\AcademicYear;

use App\Entities\BaseEntity;
use Illuminate\Database\Eloquent\Model;

final readonly class AcademicYearState extends BaseEntity
{
    public function __construct(
        private bool $isActive,
    ) {}

    public static function fromModel(Model $model): static
    {
        return new self(
            isActive: $model->is_active,
        );
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }
}
