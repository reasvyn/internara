<?php

declare(strict_types=1);

namespace App\Domain\Auth\Actions;

use App\Domain\Core\Actions\BaseAction;
use App\Domain\Core\Support\SmartLogger;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\DB;

class LockUserAccountAction extends BaseAction
{
    public function execute(User $user, string $reason = 'too_many_failed_attempts'): void
    {
        if ($user->locked_at !== null) {
            return;
        }

        $this->withErrorHandling(function () use ($user, $reason) {
            DB::transaction(function () use ($user, $reason) {
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
            });
        }, 'Failed to lock user account');
    }
}
