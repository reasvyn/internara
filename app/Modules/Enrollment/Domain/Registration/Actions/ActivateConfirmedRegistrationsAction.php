<?php

declare(strict_types=1);

namespace App\Modules\Enrollment\Domain\Registration\Actions;

use App\Modules\Core\Actions\BaseCommandAction;
use App\Modules\Enrollment\Domain\Registration\Enums\RegistrationStatus;
use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

final class ActivateConfirmedRegistrationsAction extends BaseCommandAction
{
    /**
     * Activates all confirmed registrations whose start date has arrived.
     *
     * @return Collection<int, Registration>
     */
    public function execute(?Carbon $date = null): Collection
    {
        $date ??= Carbon::today();

        return $this->transaction(function () use ($date) {
            $registrations = Registration::where('status', RegistrationStatus::CONFIRMED->value)
                ->whereDate('start_date', '<=', $date)
                ->get();

            foreach ($registrations as $registration) {
                $registration->setStatus(RegistrationStatus::ACTIVE);
                $this->log('registration_activated_by_schedule', $registration, [
                    'activated_at' => $date->toDateString(),
                    'start_date' => $registration->start_date?->toDateString(),
                ]);
            }

            return $registrations;
        });
    }
}
