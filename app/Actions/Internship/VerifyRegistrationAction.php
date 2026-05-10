<?php

declare(strict_types=1);

namespace App\Actions\Internship;

use App\Actions\Core\LogAuditAction;
use App\Models\Mentor;
use App\Models\Placement;
use App\Models\Registration;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class VerifyRegistrationAction
{
    public function __construct(protected readonly LogAuditAction $logAuditAction) {}

    public function execute(string $registrationId, array $data): Registration
    {
        return DB::transaction(function () use ($registrationId, $data) {
            /** @var Registration $registration */
            $registration = Registration::with('mentee.user', 'internship')
                ->findOrFail($registrationId);

            if (! $registration->hasStatus('pending')) {
                throw new RuntimeException('Registration is not in pending status.');
            }

            $placement = Placement::findOrFail($data['placement_id']);

            if ($placement->asPlacementCapacity()->isFull()) {
                throw new RuntimeException('Placement quota is already full.');
            }

            $registration->update([
                'placement_id' => $placement->id,
                'start_date' => $data['start_date'] ?? $placement->internship->start_date,
                'end_date' => $data['end_date'] ?? $placement->internship->end_date,
            ]);

            $registration->setStatus('active', 'Registration verified and placed by administrator.');

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
                action: 'registration_verified_and_placed',
                subjectType: Registration::class,
                subjectId: $registration->id,
                payload: $data,
                module: 'Internship',
            );

            return $registration;
        });
    }
}
