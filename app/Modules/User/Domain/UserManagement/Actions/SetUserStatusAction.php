<?php

declare(strict_types=1);

namespace App\Modules\User\Domain\UserManagement\Actions;

use App\Modules\Core\Actions\BaseCommandAction;
use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\User\Domain\AccountStatus\Notifications\AccountStatusNotification;
use App\Modules\User\Domain\UserManagement\Data\SetUserStatusData;
use App\Modules\User\Domain\UserManagement\Events\UserStatusChanged;
use App\Modules\User\Enums\AccountStatus;
use App\Modules\User\Models\User;

final class SetUserStatusAction extends BaseCommandAction
{
    public function execute(SetUserStatusData $data): User
    {
        $user = User::findOrFail($data->userId);
        $newStatus = $data->newStatus;

        if (! $data->skipAuthCheck && $user->id === auth()->id()) {
            throw new RejectedException(__('user.manager.cannot_change_own_status'));
        }

        $integrity = $user->asSuperAdminIntegrityRules();

        if (! $integrity->canBeLocked()) {
            throw new RejectedException(__('user.manager.cannot_change_super_admin_status'));
        }

        $currentStatusName = $user->status?->value;

        if ($currentStatusName !== null) {
            $currentStatus = AccountStatus::tryFrom($currentStatusName);

            if ($currentStatus && ! $currentStatus->canTransitionTo($newStatus)) {
                throw new RejectedException(
                    __('user.manager.status_invalid_transition', [
                        'from' => $currentStatus->label(),
                        'to' => $newStatus->label(),
                    ]),
                );
            }
        }

        $reason = $data->reason ?? __('user.manager.status_updated_reason');

        $user->setStatus($newStatus->value, $reason);

        $user->notify(new AccountStatusNotification($newStatus->value, $reason));

        $this->log('user_status_changed', $user, [
            'from' => $currentStatusName,
            'to' => $newStatus->value,
            'reason' => $reason,
        ]);

        event(new UserStatusChanged($user));

        return $user;
    }
}
