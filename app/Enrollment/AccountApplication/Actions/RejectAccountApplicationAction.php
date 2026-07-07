<?php

declare(strict_types=1);

namespace App\Enrollment\AccountApplication\Actions;

use App\Core\Actions\BaseCommandAction;
use App\Core\Exceptions\RejectedException;
use App\Enrollment\AccountApplication\Enums\AccountApplicationStatus;
use App\Enrollment\AccountApplication\Events\AccountApplicationRejected;
use App\Enrollment\AccountApplication\Models\AccountApplication;
use App\User\Models\User;

final class RejectAccountApplicationAction extends BaseCommandAction
{
    public function execute(string $applicationId, User $admin, string $reason): void
    {
        $application = AccountApplication::findOrFail($applicationId);

        if ($application->status !== AccountApplicationStatus::PENDING) {
            throw new RejectedException(__('registration.application_not_pending'));
        }

        $application->update([
            'status' => AccountApplicationStatus::REJECTED->value,
            'processed_by' => $admin->id,
            'processed_at' => now(),
            'rejection_reason' => $reason,
        ]);

        $this->log('account_application_rejected', $application, ['reason' => $reason]);

        event(new AccountApplicationRejected($application));
    }
}
