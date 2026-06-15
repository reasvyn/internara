<?php

declare(strict_types=1);

namespace App\Enrollment\Registration\Actions;

use App\Core\Actions\BaseCommandAction;
use App\Core\Exceptions\RejectedException;
use App\Enrollment\Placement;
use App\Enrollment\Registration\Models\Registration;
use App\User\Models\User;

final class VerifyRegistrationAction extends BaseCommandAction
{
    public function execute(string $registrationId, array $data): Registration
    {
        return $this->transaction(function () use ($registrationId, $data) {
            $registration = Registration::with('student', 'internship')->findOrFail(
                $registrationId,
            );

            if (! $registration->hasStatus('pending')) {
                throw new RejectedException(__('registration.not_pending'));
            }

            $placement = Placement::findOrFail($data['placement_id']);

            if ($placement->asPlacementCapacity()->isFull()) {
                throw new RejectedException(__('placement.quota_full'));
            }

            $registration->update([
                'placement_id' => $placement->id,
                'start_date' => $data['start_date'] ?? $placement->internship->start_date,
                'end_date' => $data['end_date'] ?? $placement->internship->end_date,
            ]);

            $registration->setStatus(
                'active',
                'Registration verified and placed by administrator.',
            );

            $placement->increment('filled_quota');

            if (! empty($data['mentor_ids'])) {
                $mentors = User::whereIn('id', $data['mentor_ids'])->get();
                $attachData = [];
                foreach ($mentors as $mentor) {
                    $attachData[$mentor->id] = ['role' => $mentor->hasRole('supervisor') ? 'supervisor' : 'teacher'];
                }
                $registration->mentors()->attach($attachData);
            }

            $this->log('registration_verified_and_placed', $registration, $data);

            return $registration;
        });
    }
}
