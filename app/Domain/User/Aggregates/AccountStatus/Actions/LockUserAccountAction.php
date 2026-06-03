<?php

declare(strict_types=1);

namespace App\Domain\User\Aggregates\AccountStatus\Actions;

use App\Domain\Core\Actions\BaseAction;
use App\Domain\Core\Support\SmartLogger;
use App\Domain\User\Models\User;
use RuntimeException;

final class LockUserAccountAction extends BaseAction
{
    public function execute(User $user, string $reason = 'too_many_failed_attempts'): void
    {
        if ($user->hasRole('super_admin')) {
            SmartLogger::warning('super_admin_lock_blocked')
                ->event('super_admin.lock_blocked')
                ->module('Auth')
                ->about($user)
                ->withPayload(['reason' => $reason])
                ->systemOnly()
                ->save();

            throw new RuntimeException('Super administrator accounts cannot be locked.');
        }

        if ($user->locked_at !== null) {
            return;
        }

        $user->update([
            'locked_at' => now(),
            'locked_reason' => $reason,
        ]);

        SmartLogger::info('user_account_locked')
            ->event('user_account_locked')
            ->module('Auth')
            ->about($user)
            ->withPayload(['reason' => $reason])
            ->activityOnly()
            ->save();
    }
}
