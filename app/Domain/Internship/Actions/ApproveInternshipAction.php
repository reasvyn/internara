<?php

declare(strict_types=1);

namespace App\Domain\Internship\Actions;

use App\Domain\Core\Actions\LogAuditAction;
use App\Domain\Internship\Models\Registration;
use App\Notifications\RegistrationNotification;
use Illuminate\Support\Facades\DB;

/**
 * S1 - Secure: Atomic approval with auditing.
 * S3 - Scalable: Stateless action.
 */
class ApproveInternshipAction
{
    public function __construct(protected readonly LogAuditAction $logAuditAction) {}

    /**
     * Execute the registration approval.
     */
    public function execute(
        Registration $registration,
        string $comment = 'Approved by administrator.',
    ): void {
        DB::transaction(function () use ($registration, $comment) {
            $registration->setStatus('active', $comment);

            // Increment placement filled quota if applicable
            if ($registration->placement_id) {
                $registration->placement()->increment('filled_quota');
            }

            // Notify Student
            $registration->student->notify(
                new RegistrationNotification(
                    $registration->internship->name,
                    'active',
                    $comment,
                ),
            );

            $this->logAuditAction->execute(
                action: 'internship_approved',
                subjectType: Registration::class,
                subjectId: $registration->id,
                payload: ['comment' => $comment],
                module: 'Internship',
            );
        });
    }
}
