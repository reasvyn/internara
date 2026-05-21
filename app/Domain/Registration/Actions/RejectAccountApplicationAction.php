<?php

declare(strict_types=1);

namespace App\Domain\Registration\Actions;

use App\Domain\Core\Actions\BaseAction;
use App\Domain\Core\Exceptions\RejectedException;
use App\Domain\Registration\Enums\AccountApplicationStatus;
use App\Domain\Registration\Models\AccountApplication;
use App\Domain\User\Models\User;

class RejectAccountApplicationAction extends BaseAction
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
