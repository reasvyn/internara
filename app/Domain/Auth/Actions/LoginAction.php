<?php

declare(strict_types=1);

namespace App\Domain\Auth\Actions;

use App\Domain\Core\Actions\BaseAction;
use App\Domain\Core\Support\CacheKeys;
use App\Domain\Core\Support\SmartLogger;
use App\Domain\User\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use RuntimeException;

final class LoginAction extends BaseAction
{
    public function execute(
        string $identifier,
        string $password,
        bool $remember = false,
    ): Authenticatable {
        $loginField = Str::contains($identifier, '@') ? 'email' : 'username';

        $user = User::where($loginField, $identifier)->first();

        if ($user === null) {
            SmartLogger::info('login_failed')
                ->event('login_failed')
                ->module('Auth')
                ->withPayload(['identifier' => $identifier])
                ->activityOnly()
                ->save();

            throw new RuntimeException(__('auth.failed'));
        }

        $this->checkAccountStatus($user);

        if (! Auth::attempt([$loginField => $identifier, 'password' => $password], $remember)) {
            $this->handleFailedAttempt($user);

            throw new RuntimeException(__('auth.failed'));
        }

        $this->clearFailedAttempts($user);

        session()->regenerate();

        SmartLogger::info('login_success')
            ->event('login_success')
            ->module('Auth')
            ->about($user)
            ->activityOnly()
            ->save();

        return $user;
    }

    private function checkAccountStatus(User $user): void
    {
        $apprentice = $user->asApprentice();

        if ($apprentice->isLocked()) {
            SmartLogger::info('login_blocked')
                ->event('login_blocked')
                ->module('Auth')
                ->about($user)
                ->withPayload(['reason' => 'locked'])
                ->activityOnly()
                ->save();

            throw new RuntimeException(__('auth.blocked'));
        }

        if (! $apprentice->status()->allowsLogin()) {
            $status = $apprentice->status()->value;

            SmartLogger::info('login_blocked')
                ->event('login_blocked')
                ->module('Auth')
                ->about($user)
                ->withPayload(['status' => $status])
                ->activityOnly()
                ->save();

            throw new RuntimeException(__('auth.blocked'));
        }

        if ($apprentice->requiresSetup()) {
            SmartLogger::info('login_blocked')
                ->event('login_blocked')
                ->module('Auth')
                ->about($user)
                ->withPayload(['reason' => 'setup_required'])
                ->activityOnly()
                ->save();

            throw new RuntimeException(__('auth.blocked'));
        }
    }

    private function handleFailedAttempt(User $user): void
    {
        $cacheKey = CacheKeys::AUTH_LOGIN_FAILURES.$user->id;
        $threshold = (int) config('auth.throttle.auto_lock_threshold', 10);

        $lock = Cache::lock($cacheKey.'.lock', 5);

        $lock->block(3);

        try {
            $attempts = (int) Cache::get($cacheKey, 0) + 1;
            Cache::put($cacheKey, $attempts, now()->addHours(1));

            SmartLogger::info('login_failed')
                ->event('login_failed')
                ->module('Auth')
                ->about($user)
                ->withPayload(['attempts' => $attempts, 'threshold' => $threshold])
                ->activityOnly()
                ->save();

            if ($attempts >= $threshold) {
                app(LockUserAccountAction::class)->execute(
                    $user,
                    reason: 'too_many_failed_attempts',
                );

                Cache::forget($cacheKey);
            }
        } finally {
            $lock->release();
        }
    }

    private function clearFailedAttempts(User $user): void
    {
        Cache::forget(CacheKeys::AUTH_LOGIN_FAILURES.$user->id);
    }
}
