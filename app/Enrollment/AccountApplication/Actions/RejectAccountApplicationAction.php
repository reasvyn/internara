<?php

declare(strict_types=1);

namespace App\Enrollment\AccountApplication\Actions;

use App\Core\Actions\BaseCommandAction;
use App\Core\Data\ActionResponse;
use App\Core\Exceptions\RejectedException;
use App\Enrollment\AccountApplication\Data\RejectAccountApplicationData;
use App\Enrollment\AccountApplication\Enums\AccountApplicationStatus;
use App\Enrollment\AccountApplication\Events\AccountApplicationRejected;
use App\Enrollment\AccountApplication\Models\AccountApplication;

final class RejectAccountApplicationAction extends BaseCommandAction
{
    public function execute(RejectAccountApplicationData $data): ActionResponse
    {
        $application = AccountApplication::findOrFail($data->applicationId);

        if ($application->status !== AccountApplicationStatus::PENDING) {
            throw new RejectedException(__('registration.application_not_pending'));
        }

        $application->update([
            'status' => AccountApplicationStatus::REJECTED->value,
            'processed_by' => auth()->id(),
            'processed_at' => now(),
            'rejection_reason' => $data->reason,
        ]);

        $this->log('account_application_rejected', $application, ['reason' => $data->reason]);

        event(new AccountApplicationRejected($application));

        return ActionResponse::ok();
    }
}
