<?php

declare(strict_types=1);

namespace App\Modules\Academics\Domain\Department\Entities;

use App\Modules\Core\Entities\BaseEntity;
use Illuminate\Database\Eloquent\Model;

final readonly class DepartmentState extends BaseEntity
{
    public function __construct(private int $profileCount, private bool $hasProfiles) {}

    public static function fromModel(Model $model): static
    {
        return new self(
            profileCount: (int) ($model->profiles_count ?? $model->profiles()->count()),
            hasProfiles: $model->relationLoaded('profiles')
                ? $model->profiles->isNotEmpty()
                : $model->profiles()->exists(),
        );
    }

    public function canBeDeleted(): bool
    {
        return ! $this->hasProfiles;
    }
}
