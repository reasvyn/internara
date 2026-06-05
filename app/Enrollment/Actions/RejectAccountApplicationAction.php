<?php

declare(strict_types=1);

namespace App\Enrollment\Actions;

use App\Core\Actions\BaseAction;
use App\Exceptions\RejectedException;
use App\Enrollment\Enums\AccountApplicationStatus;
use App\Enrollment\Models\AccountApplication;
use App\User\Models\User;

final class RejectAccountApplicationAction extends BaseAction
{
    public function execute(string $applicationId, User $admin, string $reason): void
    {
        $application = AccountApplication::findOrFail($applicationId);

        if ($application->status !== AccountApplicationStatus::PENDING) {
            throw new RejectedException('Application is not in pending status.');
        }

        $application->update([
            'status' => 'rejected',
            'processed_by' => $admin->id,
            'processed_at' => now(),
            'rejection_reason' => $reason,
        ]);

        $this->log('account_application_rejected', $application, ['reason' => $reason]);
    }
}
