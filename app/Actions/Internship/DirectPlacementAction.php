<?php

declare(strict_types=1);

namespace App\Actions\Internship;

use App\Actions\Audit\LogAuditAction;
use App\Models\InternshipPlacement;
use App\Models\InternshipRegistration;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * S1 - Secure: Direct administrative placement with immediate activation.
 * S3 - Scalable: Stateless action.
 */
class DirectPlacementAction
{
    public function __construct(
        protected LogAuditAction $logAuditAction
    ) {}

    /**
     * Execute direct placement by an administrator.
     */
    public function execute(User $student, array $data): InternshipRegistration
    {
        return DB::transaction(function () use ($student, $data) {
            $placement = InternshipPlacement::findOrFail($data['placement_id']);
            
            if ($placement->isFull()) {
                abort(422, 'Placement quota is already full.');
            }

            /** @var InternshipRegistration $registration */
            $registration = InternshipRegistration::create([
                'student_id' => $student->id,
                'internship_id' => $placement->internship_id,
                'placement_id' => $placement->id,
                'academic_year' => $data['academic_year'] ?? null,
                'start_date' => $data['start_date'] ?? $placement->internship->start_date,
                'end_date' => $data['end_date'] ?? $placement->internship->end_date,
                'teacher_id' => $data['teacher_id'] ?? null,
                'mentor_id' => $data['mentor_id'] ?? null,
            ]);

            // Direct placement is active immediately
            $registration->setStatus('active', 'Directly placed by administrator.');

            // Increment filled quota
            $placement->increment('filled_quota');

            $this->logAuditAction->execute(
                action: 'direct_placement_created',
                subjectType: InternshipRegistration::class,
                subjectId: $registration->id,
                payload: $data,
                module: 'Internship'
            );

            return $registration;
        });
    }
}
