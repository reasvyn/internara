<?php

declare(strict_types=1);

namespace App\Enrollment\AccountApplication\Actions;

use App\Core\Actions\BaseCommandAction;
use App\Core\Exceptions\RejectedException;
use App\Enrollment\AccountApplication\Enums\AccountApplicationStatus;
use App\Enrollment\AccountApplication\Models\AccountApplication;

final class ApplyAccountAction extends BaseCommandAction
{
    public function execute(array $data): AccountApplication
    {
        $existing = AccountApplication::where('email', $data['email'])
            ->whereIn('status', [AccountApplicationStatus::PENDING->value, AccountApplicationStatus::APPROVED->value])
            ->exists();

        if ($existing) {
            throw new RejectedException(__('registration.application_exists'));
        }

        return $this->transaction(function () use ($data) {
            $application = AccountApplication::create(array_merge($data, ['status' => AccountApplicationStatus::PENDING->value]));

            $this->log('account_applied', $application, $data);

            return $application;
        });
    }
}
