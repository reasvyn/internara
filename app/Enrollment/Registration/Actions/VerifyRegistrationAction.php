<?php

declare(strict_types=1);

namespace App\Enrollment\Registration\Actions;

use App\Core\Actions\BaseAction;
use App\Core\Exceptions\RejectedException;
use App\Enrollment\Placement;
use App\Enrollment\Registration\Models\Registration;
use App\Guidance\Mentor\Models\Mentor;

final class VerifyRegistrationAction extends BaseAction
{
    public function execute(string $registrationId, array $data): Registration
    {
        return $this->transaction(function () use ($registrationId, $data) {
            $registration = Registration::with('mentee.user', 'internship')->findOrFail(
                $registrationId,
            );

            if (! $registration->hasStatus('pending')) {
                throw new RejectedException('Registration is not in pending status.');
            }

            $placement = Placement::findOrFail($data['placement_id']);

            if ($placement->asPlacementCapacity()->isFull()) {
                throw new RejectedException('Placement quota is already full.');
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
                $mentors = Mentor::whereIn('id', $data['mentor_ids'])->get();
                $attachData = [];
                foreach ($mentors as $mentor) {
                    $attachData[$mentor->id] = ['role' => $mentor->type];
                }
                $registration->mentors()->attach($attachData);
            }

            $this->log('registration_verified_and_placed', $registration, $data);

            return $registration;
        });
    }
}
