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

        $registration = $model->registrations()
            ->whereHas('statuses', fn ($q) => $q->where('name', 'active'))
            ->latest()
            ->first();

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

    public function isWithinInternshipPeriod(): bool
    {
        if (! $this->startDate || ! $this->endDate) {
            return false;
        }

        return Carbon::today()->between($this->startDate, $this->endDate, true);
    }

    public function canClockIn(): bool
    {
        return $this->hasActiveRegistration && $this->isWithinInternshipPeriod();
    }

    public function canSubmitLogbook(): bool
    {
        return $this->hasActiveRegistration && $this->isWithinInternshipPeriod();
    }

    public function canSubmitAssignment(): bool
    {
        return $this->hasActiveRegistration;
    }

    public function hasEnded(): bool
    {
        if (! $this->endDate) {
            return false;
        }

        return Carbon::today()->isAfter($this->endDate);
    }

    public function daysRemaining(): int
    {
        if (! $this->endDate) {
            return 0;
        }

        return max(0, (int) Carbon::today()->diffInDays($this->endDate, false));
    }
}
