<?php

declare(strict_types=1);

namespace App\Modules\Enrollment\Domain\AccountApplication\Actions;

use App\Modules\Core\Actions\BaseCommandAction;
use App\Modules\Core\Data\ActionResponse;
use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\Enrollment\Domain\AccountApplication\Data\RejectAccountApplicationData;
use App\Modules\Enrollment\Domain\AccountApplication\Enums\AccountApplicationStatus;
use App\Modules\Enrollment\Domain\AccountApplication\Events\AccountApplicationRejected;
use App\Modules\Enrollment\Domain\AccountApplication\Models\AccountApplication;

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
