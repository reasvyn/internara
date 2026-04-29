<?php

declare(strict_types=1);

namespace App\Actions\Internship;

use App\Actions\Audit\LogAuditAction;
use App\Models\InternshipRegistration;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * S1 - Secure: Atomic registration with auditing.
 * S3 - Scalable: Stateless action.
 */
class RegisterInternshipAction
{
    public function __construct(
        protected LogAuditAction $logAuditAction
    ) {}

    /**
     * Execute the student internship registration.
     */
    public function execute(User $student, array $data): InternshipRegistration
    {
        return DB::transaction(function () use ($student, $data) {
            /** @var InternshipRegistration $registration */
            $registration = InternshipRegistration::create([
                'student_id' => $student->id,
                'internship_id' => $data['internship_id'],
                'placement_id' => $data['placement_id'] ?? null,
                'academic_year' => $data['academic_year'] ?? null,
                'start_date' => $data['start_date'] ?? null,
                'end_date' => $data['end_date'] ?? null,
                'proposed_company_name' => $data['proposed_company_name'] ?? null,
                'proposed_company_address' => $data['proposed_company_address'] ?? null,
            ]);

            $registration->setStatus('pending', 'Initial registration submitted by student.');

            $this->logAuditAction->execute(
                action: 'internship_registered',
                subjectType: InternshipRegistration::class,
                subjectId: $registration->id,
                payload: $data,
                module: 'Internship'
            );

            return $registration;
        });
    }
}
