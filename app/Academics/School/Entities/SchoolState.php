<?php

declare(strict_types=1);

namespace App\Academics\School\Entities;

use App\Core\Entities\BaseEntity;
use Illuminate\Database\Eloquent\Model;

final readonly class SchoolState extends BaseEntity
{
    public function __construct(
        private int $existsCount,
    ) {}

    public static function fromModel(Model $model): static
    {
        return new self(
            existsCount: $model->exists ? 1 : 0,
        );
    }

    public function canBeCreated(): bool
    {
        return $this->existsCount === 0;
    }
}
