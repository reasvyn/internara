<?php

declare(strict_types=1);

namespace App\Domain\Program\Aggregates\Internship\Entities;

use App\Domain\Core\Entities\BaseEntity;
use App\Domain\Program\Aggregates\Internship\Enums\InternshipStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

final readonly class InternshipPeriod extends BaseEntity
{
    public function __construct(
        private ?InternshipStatus $status,
        private ?Carbon $registrationStartDate = null,
        private ?Carbon $registrationEndDate = null,
        private ?Carbon $academicYearStart = null,
        private ?Carbon $academicYearEnd = null,
    ) {}

    public static function fromModel(Model $model): static
    {
        $academicYear = $model->relationLoaded('academicYear') ? $model->academicYear : null;

        return new self(
            status: $model->status,
            registrationStartDate: $model->registration_start_date,
            registrationEndDate: $model->registration_end_date,
            academicYearStart: $academicYear?->start_date,
            academicYearEnd: $academicYear?->end_date,
        );
    }

    public function isAcceptingRegistrations(?Carbon $now = null): bool
    {
        $now ??= new Carbon;

        if (! $this->status?->isAcceptingRegistrations()) {
            return false;
        }

        if ($this->registrationStartDate !== null && $now->lt($this->registrationStartDate)) {
            return false;
        }

        if ($this->registrationEndDate !== null && $now->gt($this->registrationEndDate)) {
            return false;
        }

        return true;
    }

    public function isRegistrationWindowOpen(?Carbon $now = null): bool
    {
        $now ??= new Carbon;

        if ($this->registrationStartDate !== null && $now->lt($this->registrationStartDate)) {
            return false;
        }

        if ($this->registrationEndDate !== null && $now->gt($this->registrationEndDate)) {
            return false;
        }

        return true;
    }

    public function isBeforeRegistrationWindow(?Carbon $now = null): bool
    {
        $now ??= new Carbon;

        return $this->registrationStartDate !== null && $now->lt($this->registrationStartDate);
    }

    public function isAfterRegistrationWindow(?Carbon $now = null): bool
    {
        $now ??= new Carbon;

        return $this->registrationEndDate !== null && $now->gt($this->registrationEndDate);
    }

    public function hasAcademicYear(): bool
    {
        return $this->academicYearStart !== null && $this->academicYearEnd !== null;
    }

    public function isWithinAcademicYear(?Carbon $date = null): bool
    {
        if (! $this->hasAcademicYear()) {
            return true;
        }

        $date ??= new Carbon;

        return $date->between($this->academicYearStart, $this->academicYearEnd, true);
    }

    public function datesSpanOutsideAcademicYear(?Carbon $start = null, ?Carbon $end = null): bool
    {
        if (! $this->hasAcademicYear() || $start === null || $end === null) {
            return false;
        }

        return $start->lt($this->academicYearStart) || $end->gt($this->academicYearEnd);
    }
}
