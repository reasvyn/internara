<?php

declare(strict_types=1);

namespace App\Modules\Enrollment\Domain\Registration\Actions;

use App\Modules\Core\Actions\BaseCommandAction;
use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\Enrollment\Domain\Registration\Data\RejectRegistrationData;
use App\Modules\Enrollment\Domain\Registration\Events\RegistrationRejected;
use App\Modules\Enrollment\Domain\Registration\Models\Registration;

final class RejectRegistrationAction extends BaseCommandAction
{
    public function execute(RejectRegistrationData $data): Registration
    {
        return $this->transaction(function () use ($data) {
            $registration = Registration::with('student', 'internship')->findOrFail(
                $data->registrationId,
            );

            if (! $registration->hasStatus('pending')) {
                throw new RejectedException(__('registration.not_pending'));
            }

            $registration->update([
                'status' => 'rejected',
                'rejection_reason' => $data->reason,
            ]);

            $this->log('registration_rejected', $registration, ['reason' => $data->reason]);

            $this->dispatchEvent(new RegistrationRejected($registration, $data->reason));

            return $registration;
        });
    }
}
