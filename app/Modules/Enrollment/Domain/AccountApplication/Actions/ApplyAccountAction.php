<?php

declare(strict_types=1);

namespace App\Modules\Enrollment\Domain\AccountApplication\Actions;

use App\Modules\Core\Actions\BaseCommandAction;
use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\Enrollment\Domain\AccountApplication\Enums\AccountApplicationStatus;
use App\Modules\Enrollment\Domain\AccountApplication\Models\AccountApplication;
use App\Modules\User\Models\User;

final class ApplyAccountAction extends BaseCommandAction
{
    public function execute(array $data): AccountApplication
    {
        $data['form_data'] = array_map(
            fn (mixed $value): mixed => is_string($value) ? strip_tags($value) : $value,
            $data['form_data'] ?? [],
        );

        $existingUser = User::where('email', $data['email'])->exists();
        $existingApplication = AccountApplication::where('email', $data['email'])
            ->whereIn('status', [AccountApplicationStatus::PENDING->value, AccountApplicationStatus::APPROVED->value])
            ->exists();

        if ($existingUser || $existingApplication) {
            throw new RejectedException(__('registration.application_exists'));
        }

        return $this->transaction(function () use ($data) {
            $existingRejected = AccountApplication::where('email', $data['email'])
                ->where('status', AccountApplicationStatus::REJECTED->value)
                ->first();

            if ($existingRejected) {
                $existingRejected->update(array_merge($data, ['status' => AccountApplicationStatus::PENDING->value]));

                $this->log('account_applied', $existingRejected, $data);

                return $existingRejected->fresh();
            }

            $application = AccountApplication::create(array_merge($data, ['status' => AccountApplicationStatus::PENDING->value]));

            $this->log('account_applied', $application, $data);

            return $application;
        });
    }
}
