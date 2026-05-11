<?php

declare(strict_types=1);

namespace App\Entities\Internship;

use App\Entities\BaseEntity;
use App\Enums\Internship\InternshipStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

final readonly class InternshipPeriod extends BaseEntity
{
    public function __construct(
        private ?InternshipStatus $status,
        private ?Carbon $registrationStartDate = null,
        private ?Carbon $registrationEndDate = null,
    ) {}

    public static function fromModel(Model $model): static
    {
        return new self(
            status: $model->status,
            registrationStartDate: $model->registration_start_date,
            registrationEndDate: $model->registration_end_date,
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
}
