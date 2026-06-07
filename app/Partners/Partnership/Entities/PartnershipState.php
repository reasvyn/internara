<?php

declare(strict_types=1);

namespace App\Partners\Partnership\Entities;

use App\Core\Entities\BaseEntity;
use App\Partners\Partnership\Enums\PartnershipStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

final readonly class PartnershipState extends BaseEntity
{
    public function __construct(private PartnershipStatus $status, private ?string $endDate) {}

    public static function fromModel(Model $model): static
    {
        return new self(status: $model->status, endDate: $model->end_date?->format('Y-m-d'));
    }

    public function isActive(): bool
    {
        return $this->status === PartnershipStatus::ACTIVE;
    }

    public function isExpired(): bool
    {
        return $this->status === PartnershipStatus::EXPIRED;
    }

    public function isTerminated(): bool
    {
        return $this->status === PartnershipStatus::TERMINATED;
    }

    public function isExpiringSoon(int $thresholdDays = 30): bool
    {
        if (! $this->isActive() || $this->endDate === null) {
            return false;
        }

        $end = Carbon::parse($this->endDate);
        $diff = (int) ceil(now()->diffInDays($end, false));

        return $diff >= 0 && $diff <= $thresholdDays;
    }

    public function canBeDeleted(): bool
    {
        return $this->isExpired() || $this->isTerminated();
    }
}
