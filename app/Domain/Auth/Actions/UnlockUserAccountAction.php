<?php

declare(strict_types=1);

namespace App\Domain\Auth\Actions;

use App\Domain\Core\Actions\BaseAction;
use App\Domain\Core\Support\SmartLogger;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class UnlockUserAccountAction extends BaseAction
{
    public function execute(User $user): void
    {
        if ($user->hasRole('super_admin')) {
            SmartLogger::warning('super_admin_unlock_blocked')
                ->event('super_admin.unlock_blocked')
                ->module('Auth')
                ->about($user)
                ->systemOnly()
                ->save();

            throw new RuntimeException('Super administrator accounts cannot be unlocked — they cannot be locked.');
        }

        if ($user->locked_at === null) {
            return;
        }

        $this->withErrorHandling(function () use ($user) {
            DB::transaction(function () use ($user) {
                $user->update([
                    'locked_at' => null,
                    'locked_reason' => null,
                ]);

                SmartLogger::info('user_account_unlocked')
                    ->event('user_account_unlocked')
                    ->module('Auth')
                    ->about($user)
                    ->activityOnly()
                    ->save();
            });
        }, 'Failed to unlock user account');
    }
}
