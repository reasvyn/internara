<?php

declare(strict_types=1);

namespace App\User\AccountStatus\Actions;

use App\Core\Actions\BaseCommandAction;
use App\Core\Exceptions\RejectedException;
use App\User\AccountStatus\Events\UserAccountUnlocked;
use App\User\Models\User;

class UnlockUserAccountAction extends BaseCommandAction
{
    public function execute(User $user): void
    {
        if ($user->hasRole('super_admin')) {
            $this->log('super_admin_unlock_blocked', $user);

            throw new RejectedException(
                'Super administrator accounts cannot be unlocked — they cannot be locked.',
            );
        }

        if ($user->locked_at === null) {
            return;
        }

        $this->withErrorHandling(function () use ($user) {
            $this->transaction(function () use ($user) {
                $user->update([
                    'locked_at' => null,
                    'locked_reason' => null,
                ]);

                $this->log('user_account_unlocked', $user);

                event(new UserAccountUnlocked($user));
            });
        }, 'Failed to unlock user account');
    }
}
