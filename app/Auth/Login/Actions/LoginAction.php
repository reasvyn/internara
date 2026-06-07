<?php

declare(strict_types=1);

namespace App\Auth\Login\Actions;

use App\Core\Actions\BaseAction;
use App\Core\Support\SmartLogger;
use App\User\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

final class LoginAction extends BaseAction
{
    public function execute(
        string $identifier,
        string $password,
        bool $remember = false,
    ): Authenticatable {
        $identifierHash = md5($identifier);
        $lockoutKey = "login:lockout:{$identifierHash}";

        $lockoutUntil = Cache::get($lockoutKey);
        if ($lockoutUntil !== null) {
            $lockoutTime = Carbon::parse($lockoutUntil);
            if (now()->lt($lockoutTime)) {
                $seconds = (int) ceil(now()->diffInSeconds($lockoutTime));
                throw new RuntimeException(
                    __('auth.throttle', ['seconds' => $seconds]) ?:
                    "Too many login attempts. Please try again in {$seconds} seconds.",
                );
            }
        }

        $loginField = filter_var($identifier, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        $user = User::where($loginField, $identifier)->first();

        if ($user === null) {
            $this->handleFailedAttempt($identifier);

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
            $this->handleFailedAttempt($identifier);

            throw new RuntimeException(__('auth.failed'));
        }

        $this->clearFailedAttempts($identifier);

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

    private function handleFailedAttempt(string $identifier): void
    {
        $identifierHash = md5($identifier);
        $attemptsKey = "login:attempts:{$identifierHash}";
        $lockoutKey = "login:lockout:{$identifierHash}";

        $attempts = (int) Cache::get($attemptsKey, 0) + 1;
        Cache::put($attemptsKey, $attempts, now()->addHours(24));

        if ($attempts >= 10) {
            $durationSeconds = 10 * 2 ** ($attempts - 10);
            Cache::put(
                $lockoutKey,
                now()->addSeconds($durationSeconds),
                now()->addSeconds($durationSeconds),
            );

            SmartLogger::info('login_throttle_triggered')
                ->event('login_throttle_triggered')
                ->module('Auth')
                ->withPayload([
                    'identifier' => $identifier,
                    'attempts' => $attempts,
                    'duration_seconds' => $durationSeconds,
                ])
                ->activityOnly()
                ->save();
        }
    }

    private function clearFailedAttempts(string $identifier): void
    {
        $identifierHash = md5($identifier);
        Cache::forget("login:attempts:{$identifierHash}");
        Cache::forget("login:lockout:{$identifierHash}");
    }
}
