<?php

declare(strict_types=1);

namespace App\User\UserManagement\Actions;

use App\Core\Actions\BaseCommandAction;
use App\Core\Exceptions\RejectedException;
use App\User\AccountStatus\Notifications\AccountStatusNotification;
use App\User\Enums\AccountStatus;
use App\User\Models\User;
use App\User\UserManagement\Events\UserStatusChanged;

final class ToggleUserStatusAction extends BaseCommandAction
{
    public function execute(User $user, ?string $reason = null): User
    {
        if ($user->id === auth()->id()) {
            throw new RejectedException('Cannot change your own status.');
        }

        $integrity = $user->asSuperAdminIntegrityRules();

        if (!$integrity->canBeLocked()) {
            throw new RejectedException('Cannot toggle super admin account status.');
        }

        return $this->transaction(function () use ($user, $reason) {
            $currentStatus = $user->status?->value;
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
