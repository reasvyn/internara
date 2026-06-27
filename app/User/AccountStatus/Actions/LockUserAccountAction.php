<?php

declare(strict_types=1);

namespace App\User\AccountStatus\Actions;

use App\Core\Actions\BaseCommandAction;
use App\Core\Exceptions\RejectedException;
use App\User\AccountStatus\Events\UserAccountLocked;
use App\User\Models\User;

final class LockUserAccountAction extends BaseCommandAction
{
    public function execute(User $user, string $reason = 'too_many_failed_attempts'): void
    {
        if ($user->hasRole('super_admin')) {
            $this->log('super_admin_lock_blocked', $user, ['reason' => $reason]);

            throw new RejectedException('Super administrator accounts cannot be locked.');
        }

        if ($user->locked_at !== null) {
            return;
        }

        $this->transaction(function () use ($user, $reason) {
            $user->update([
                'locked_at' => now(),
                'locked_reason' => $reason,
            ]);

            $this->log('user_account_locked', $user, ['reason' => $reason]);

            event(new UserAccountLocked($user));
        });
    }
}
