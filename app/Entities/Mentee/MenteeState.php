<?php

declare(strict_types=1);

namespace App\Entities\Mentee;

use App\Entities\BaseEntity;
use App\Models\Mentee;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

final readonly class MenteeState extends BaseEntity
{
    public function __construct(
        private bool $hasActiveRegistration,
        private ?Carbon $startDate,
        private ?Carbon $endDate,
        private bool $isActive,
    ) {}

    public static function fromModel(Model $model): static
    {
        assert($model instanceof Mentee);

        $registration = $model->latestActiveRegistration();

        return new self(
            hasActiveRegistration: $registration !== null,
            startDate: $registration?->start_date,
            endDate: $registration?->end_date,
            isActive: (bool) $model->is_active,
        );
    }

    public function hasActiveRegistration(): bool
    {
        return $this->hasActiveRegistration;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function isWithinInternshipPeriod(?Carbon $today = null): bool
    {
        $today ??= new Carbon;

        if (! $this->startDate || ! $this->endDate) {
            return false;
        }

        return $today->between($this->startDate, $this->endDate, true);
    }

    public function canClockIn(?Carbon $today = null): bool
    {
        return $this->hasActiveRegistration && $this->isWithinInternshipPeriod($today);
    }

    public function canSubmitLogbook(?Carbon $today = null): bool
    {
        return $this->hasActiveRegistration && $this->isWithinInternshipPeriod($today);
    }

    public function canSubmitAssignment(): bool
    {
        return $this->hasActiveRegistration;
    }

    public function hasEnded(?Carbon $today = null): bool
    {
        $today ??= new Carbon;

        if (! $this->endDate) {
            return false;
        }

        return $today->isAfter($this->endDate);
    }

    public function daysRemaining(?Carbon $today = null): int
    {
        $today ??= new Carbon;

        if (! $this->endDate) {
            return 0;
        }

        return max(0, (int) $today->diffInDays($this->endDate, false));
    }
}
