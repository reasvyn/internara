<?php

declare(strict_types=1);

namespace App\Entities\Department;

use App\Entities\BaseEntity;
use Illuminate\Database\Eloquent\Model;

final readonly class DepartmentState extends BaseEntity
{
    public function __construct(
        private int $profileCount,
    ) {}

    public static function fromModel(Model $model): static
    {
        return new self(
            profileCount: (int) ($model->profiles_count ?? $model->profiles()->count()),
        );
    }

    public function canBeDeleted(): bool
    {
        return $this->profileCount === 0;
    }
}
