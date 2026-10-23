<?php

declare(strict_types=1);

namespace App\Modules\Enrollment\Domain\Registration\Actions;

use App\Modules\Core\Actions\BaseCommandAction;
use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\Enrollment\Domain\Placement\Models\Placement;
use App\Modules\Enrollment\Domain\Registration\Data\VerifyRegistrationData;
use App\Modules\Enrollment\Domain\Registration\Events\RegistrationVerified;
use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use App\Modules\User\Models\User;

final class VerifyRegistrationAction extends BaseCommandAction
{
    public function execute(VerifyRegistrationData $data): Registration
    {
        return $this->transaction(function () use ($data) {
            $registration = Registration::with('student', 'internship', 'documents')->findOrFail(
                $data->registrationId,
            );

            if (! $registration->hasStatus('pending')) {
                throw new RejectedException(__('registration.not_pending'));
            }

            if ($registration->documents->isNotEmpty() && $registration->documents->contains(fn ($doc) => ! $doc->status->isVerified())) {
                throw new RejectedException(__('registration.documents_not_verified'));
            }

            $placement = Placement::findOrFail($data->placementId);

            if ($placement->asPlacementCapacity()->isFull()) {
                throw new RejectedException(__('placement.quota_full'));
            }

            $registration->update([
                'placement_id' => $placement->id,
                'start_date' => $data->startDate ?? $placement->internship->start_date,
                'end_date' => $data->endDate ?? $placement->internship->end_date,
            ]);

            $registration->setStatus(
                'active',
                'Registration verified and placed by administrator.',
            );

            $placement->increment('filled_quota');

            if ($data->mentorIds !== []) {
                $mentors = User::whereIn('id', $data->mentorIds)->get();
                $attachData = [];
                foreach ($mentors as $mentor) {
                    $attachData[$mentor->id] = ['role' => $mentor->hasRole('supervisor') ? 'supervisor' : 'teacher'];
                }
                $registration->mentors()->attach($attachData);
            }

            $this->log('registration_verified_and_placed', $registration, $data->toArray());

            $this->dispatchEvent(new RegistrationVerified($registration));

            return $registration;
        });
    }
}
