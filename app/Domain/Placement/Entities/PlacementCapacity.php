<?php

declare(strict_types=1);

namespace App\Domain\Placement\Entities;

use App\Domain\Core\Entities\BaseEntity;
use Illuminate\Database\Eloquent\Model;

final readonly class PlacementCapacity extends BaseEntity
{
    public function __construct(
        private int $quota,
        private int $filledQuota,
    ) {}

    public static function fromModel(Model $model): static
    {
        return new self(
            quota: $model->quota,
            filledQuota: $model->filled_quota,
        );
    }

    public function isFull(): bool
    {
        return $this->filledQuota >= $this->quota;
    }

    public function availableSlots(): int
    {
        return max(0, $this->quota - $this->filledQuota);
    }

    public function hasAvailableSlots(): bool
    {
        return $this->availableSlots() > 0;
    }
}
