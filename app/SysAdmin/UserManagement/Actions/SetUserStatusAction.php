<?php

declare(strict_types=1);

namespace App\SysAdmin\UserManagement\Actions;

use App\Auth\SuperAdmin\Entities\SuperAdminIntegrityRules;
use App\Core\Actions\BaseAction;
use App\Core\Exceptions\RejectedException;
use App\User\AccountStatus\Notifications\AccountStatusNotification;
use App\User\Enums\AccountStatus;
use App\User\Models\User;

final class SetUserStatusAction extends BaseAction
{
    public function execute(User $user, AccountStatus $newStatus, ?string $reason = null): User
    {
        if ($user->id === auth()->id()) {
            throw new RejectedException('Cannot change your own status.');
        }

        $user->super_admin_count = User::role('super_admin')->count();

        $integrity = SuperAdminIntegrityRules::fromModel($user);

        if (! $integrity->canBeLocked()) {
            throw new RejectedException('Cannot change super admin account status.');
        }

        $currentStatusName = $user->latestStatus()?->name;

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

        $reason ??= __('user.manager.status_updated_reason');

        $user->setStatus($newStatus->value, $reason);

        $user->notify(new AccountStatusNotification($newStatus->value, $reason));

        $this->log('user_status_changed', $user, [
            'from' => $currentStatusName,
            'to' => $newStatus->value,
            'reason' => $reason,
        ]);

        return $user;
    }
}
