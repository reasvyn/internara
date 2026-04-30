<?php

declare(strict_types=1);

namespace App\Actions\Internship;

use App\Actions\Audit\LogAuditAction;
use App\Models\InternshipRegistration;
use Illuminate\Support\Facades\DB;

/**
 * S1 - Secure: Atomic supervisor assignment with auditing.
 * S3 - Scalable: Stateless action.
 */
class AssignSupervisorAction
{
    public function __construct(
        protected readonly LogAuditAction $logAuditAction
    ) {}

    /**
     * Execute the supervisor assignment.
     */
    public function execute(InternshipRegistration $registration, ?string $teacherId = null, ?string $mentorId = null): void
    {
        DB::transaction(function () use ($registration, $teacherId, $mentorId) {
            $registration->update(array_filter([
                'teacher_id' => $teacherId,
                'mentor_id' => $mentorId,
            ], fn($v) => $v !== null));

            $this->logAuditAction->execute(
                action: 'supervisors_assigned',
                subjectType: InternshipRegistration::class,
                subjectId: $registration->id,
                payload: [
                    'teacher_id' => $teacherId,
                    'mentor_id' => $mentorId,
                ],
                module: 'Internship'
            );
        });
    }
}
