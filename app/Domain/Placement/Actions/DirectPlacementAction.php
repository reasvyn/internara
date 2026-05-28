<?php

declare(strict_types=1);

namespace App\Domain\Placement\Actions;

use App\Domain\Core\Actions\BaseAction;
use App\Domain\Core\Exceptions\RejectedException;
use App\Domain\Mentee\Models\Mentee;
use App\Domain\Mentor\Models\Mentor;
use App\Domain\Placement\Models\Placement;
use App\Domain\Registration\Models\Registration;
use App\Domain\User\Models\User;

final class DirectPlacementAction extends BaseAction
{
    public function execute(User $student, array $data): Registration
    {
        return $this->transaction(function () use ($student, $data) {
            $placement = Placement::findOrFail($data['placement_id']);

            if ($placement->asPlacementCapacity()->isFull()) {
                throw new RejectedException('Placement quota is already full.');
            }

            /** @var Mentee $mentee */
            $mentee = Mentee::create([
                'user_id' => $student->id,
            ]);

            /** @var Registration $registration */
            $registration = Registration::create([
                'mentee_id' => $mentee->id,
                'internship_id' => $placement->internship_id,
                'placement_id' => $placement->id,
                'academic_year' => $data['academic_year'] ?? null,
                'start_date' => $data['start_date'] ?? $placement->internship->start_date,
                'end_date' => $data['end_date'] ?? $placement->internship->end_date,
            ]);

            $registration->setStatus('active', 'Directly placed by administrator.');

            $placement->increment('filled_quota');

            if (! empty($data['mentor_ids'])) {
                $mentors = Mentor::whereIn('id', $data['mentor_ids'])->get();
                $attachData = [];
                foreach ($mentors as $mentor) {
                    $attachData[$mentor->id] = ['role' => $mentor->type];
                }
                $registration->mentors()->attach($attachData);
            }

            $this->log('direct_placement_created', $registration, $data);

            return $registration;
        });
    }
}
