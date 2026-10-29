<?php

declare(strict_types=1);

namespace App\Modules\Enrollment\Domain\Registration\Actions;

use App\Modules\Core\Actions\BaseCommandAction;
use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\Enrollment\Domain\Registration\Enums\RegistrationStatus;
use App\Modules\Enrollment\Domain\Registration\Models\Registration;

final class CompleteRegistrationAction extends BaseCommandAction
{
    public function execute(Registration $registration, ?string $reason = null): Registration
    {
        if (! $registration->hasStatus(RegistrationStatus::ACTIVE)) {
            throw new RejectedException(__('registration.not_active_to_complete'));
        }

        return $this->transaction(function () use ($registration, $reason) {
            $registration->setStatus(RegistrationStatus::COMPLETED, $reason);

            $this->log('registration_completed_permanently', $registration, [
                'completed_at' => now()->toIso8601String(),
                'reason' => $reason,
            ]);

            return $registration;
        });
    }
}
