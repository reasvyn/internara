<?php

declare(strict_types=1);

namespace App\Enrollment\Entities;

use App\Core\Entities\BaseEntity;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

final readonly class RegistrationState extends BaseEntity
{
    public function __construct(
        private ?string $status,
        private ?Carbon $startDate,
        private ?Carbon $endDate,
        private bool $hasPlacement,
    ) {}

    public static function fromModel(Model $model): static
    {
        return new self(
            status: $model->getAttribute('status'),
            startDate: $model->start_date,
            endDate: $model->end_date,
            hasPlacement: $model->placement_id !== null,
        );
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isCurrentlyOngoing(?Carbon $today = null): bool
    {
        $today ??= new Carbon;

        if (! $this->startDate || ! $this->endDate) {
            return false;
        }

        return $today->between($this->startDate, $this->endDate, true);
    }

    public function hasEnded(?Carbon $today = null): bool
    {
        $today ??= new Carbon;

        if (! $this->endDate) {
            return false;
        }

        return $today->isAfter($this->endDate);
    }

    public function canBeApproved(): bool
    {
        return $this->isPending() && $this->hasPlacement;
    }

    public function daysRemaining(?Carbon $today = null): int
    {
        $today ??= new Carbon;

        if (! $this->endDate) {
            return 0;
        }

        return max(0, (int) $today->diffInDays($this->endDate, false));
    }

    public function totalDuration(): int
    {
        if (! $this->startDate || ! $this->endDate) {
            return 0;
        }

        return (int) $this->startDate->diffInDays($this->endDate);
    }
}
