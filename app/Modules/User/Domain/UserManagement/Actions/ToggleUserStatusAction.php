<?php

declare(strict_types=1);

namespace App\Modules\User\Domain\UserManagement\Actions;

use App\Modules\Core\Actions\BaseCommandAction;
use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\User\Domain\AccountStatus\Notifications\AccountStatusNotification;
use App\Modules\User\Domain\UserManagement\Events\UserStatusChanged;
use App\Modules\User\Enums\AccountStatus;
use App\Modules\User\Models\User;

final class ToggleUserStatusAction extends BaseCommandAction
{
    public function execute(User $user, ?string $reason = null): User
    {
        if ($user->id === auth()->id()) {
            throw new RejectedException(__('user.manager.cannot_change_own_status'));
        }

        $integrity = $user->asSuperAdminIntegrityRules();

        if (! $integrity->canBeLocked()) {
            throw new RejectedException(__('user.manager.cannot_change_super_admin_status'));
        }

        return $this->transaction(function () use ($user, $reason) {
            $currentStatus = $user->status->value;
            $newStatus =
                $currentStatus === AccountStatus::VERIFIED->value
                    ? AccountStatus::SUSPENDED->value
                    : AccountStatus::VERIFIED->value;

            $user->setStatus($newStatus, $reason ?? 'Toggled via User Manager');

            $user->notify(
                new AccountStatusNotification($newStatus, $reason ?? 'Updated by Administrator'),
            );

            $this->log('user_status_toggled', $user, [
                'previous_status' => $currentStatus,
                'new_status' => $newStatus,
            ]);

            event(new UserStatusChanged($user));

            return $user;
        });
    }
}
