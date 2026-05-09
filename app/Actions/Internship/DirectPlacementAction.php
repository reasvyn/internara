<?php

declare(strict_types=1);

namespace App\Actions\Internship;

use App\Actions\Core\LogAuditAction;
use App\Models\Mentee;
use App\Models\Mentor;
use App\Models\Placement;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DirectPlacementAction
{
    public function __construct(protected readonly LogAuditAction $logAuditAction) {}

    public function execute(User $student, array $data): Registration
    {
        return DB::transaction(function () use ($student, $data) {
            $placement = Placement::findOrFail($data['placement_id']);

            if ($placement->asPlacementCapacity()->isFull()) {
                abort(422, 'Placement quota is already full.');
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

            $this->logAuditAction->execute(
                action: 'direct_placement_created',
                subjectType: Registration::class,
                subjectId: $registration->id,
                payload: $data,
                module: 'Internship',
            );

            return $registration;
        });
    }
}
