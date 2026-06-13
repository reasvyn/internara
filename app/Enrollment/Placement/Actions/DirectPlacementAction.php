<?php

declare(strict_types=1);

namespace App\Enrollment\Placement\Actions;

use App\Core\Actions\BaseAction;
use App\Core\Exceptions\RejectedException;
use App\Enrollment\Placement;
use App\Enrollment\Registration\Models\Registration;
use App\User\Models\User;

final class DirectPlacementAction extends BaseAction
{
    public function execute(User $student, array $data): Registration
    {
        return $this->transaction(function () use ($student, $data) {
            $placement = Placement::findOrFail($data['placement_id']);

            if ($placement->asPlacementCapacity()->isFull()) {
                throw new RejectedException(__('placement.quota_full'));
            }

            /** @var Registration $registration */
            $registration = Registration::create([
                'student_id' => $student->id,
                'internship_id' => $placement->internship_id,
                'placement_id' => $placement->id,
                'start_date' => $data['start_date'] ?? $placement->internship->start_date,
                'end_date' => $data['end_date'] ?? $placement->internship->end_date,
            ]);

            // @todo Replace with enum value when RegistrationStatus enum exists
            $registration->setStatus('active', 'Directly placed by administrator.');

            $placement->increment('filled_quota');

            if (! empty($data['mentor_ids'])) {
                $mentors = User::whereIn('id', $data['mentor_ids'])->get();
                $attachData = [];
                foreach ($mentors as $mentor) {
                    $attachData[$mentor->id] = ['role' => $mentor->hasRole('supervisor') ? 'supervisor' : 'teacher'];
                }
                $registration->mentors()->attach($attachData);
            }

            $this->log('direct_placement_created', $registration, $data);

            return $registration;
        });
    }
}
