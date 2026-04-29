<?php

declare(strict_types=1);

namespace App\Actions\Internship;

use App\Actions\Audit\LogAuditAction;
use App\Models\InternshipRegistration;
use Illuminate\Support\Facades\DB;

/**
 * S1 - Secure: Atomic approval with auditing.
 * S3 - Scalable: Stateless action.
 */
class ApproveInternshipAction
{
    public function __construct(
        protected LogAuditAction $logAuditAction
    ) {}

    /**
     * Execute the registration approval.
     */
    public function execute(InternshipRegistration $registration, string $comment = 'Approved by administrator.'): void
    {
        DB::transaction(function () use ($registration, $comment) {
            $registration->setStatus('active', $comment);

            // Increment placement filled quota if applicable
            if ($registration->placement_id) {
                $registration->placement()->increment('filled_quota');
            }

            $this->logAuditAction->execute(
                action: 'internship_approved',
                subjectType: InternshipRegistration::class,
                subjectId: $registration->id,
                payload: ['comment' => $comment],
                module: 'Internship'
            );
        });
    }
}
