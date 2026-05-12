<?php

declare(strict_types=1);

namespace App\Actions\Internship;

use App\Actions\Core\LogAuditAction;
use App\Models\AccountApplication;
use App\Models\User;
use RuntimeException;

class RejectAccountApplicationAction
{
    public function __construct(
        protected readonly LogAuditAction $logAuditAction,
    ) {}

    public function execute(string $applicationId, User $admin, string $reason): void
    {
        $application = AccountApplication::findOrFail($applicationId);

        if ($application->status !== 'pending') {
            throw new RuntimeException('Application is not in pending status.');
        }

        $application->update([
            'status' => 'rejected',
            'processed_by' => $admin->id,
            'processed_at' => now(),
            'rejection_reason' => $reason,
        ]);

        $this->logAuditAction->execute(
            action: 'account_application_rejected',
            subjectType: AccountApplication::class,
            subjectId: $application->id,
            payload: ['reason' => $reason],
            module: 'Internship',
        );
    }
}
