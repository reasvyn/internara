<?php

declare(strict_types=1);

namespace App\Auth\Login\Actions;

use App\Auth\Login\Data\LoginData;
use App\Auth\Login\Events\LoginFailed;
use App\Auth\Login\Events\LoginSucceeded;
use App\Core\Actions\BaseAction;
use App\Core\Support\SmartLogger;
use App\User\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use RuntimeException;

final class LoginAction extends BaseAction
{
    public function execute(
        string $identifier,
        string $password,
        bool $remember = false,
    ): Authenticatable {
        $data = new LoginData(identifier: $identifier, password: $password, remember: $remember);
        $identifierHash = md5($data->identifier);

        $this->checkLockout($identifierHash);

        $loginField = filter_var($data->identifier, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        $user = User::where($loginField, $data->identifier)->first();

        if ($user === null) {
            $this->handleFailedAttempt($identifierHash, $data->identifier);
            Event::dispatch(new LoginFailed($data->identifier, 'user_not_found'));
            throw new RuntimeException(__('auth.failed'));
        }

        $this->checkAccountStatus($user, $data->identifier);

        if (! Auth::attempt([$loginField => $data->identifier, 'password' => $data->password], $data->remember)) {
            $this->handleFailedAttempt($identifierHash, $data->identifier);
            Event::dispatch(new LoginFailed($data->identifier, 'invalid_password'));
            throw new RuntimeException(__('auth.failed'));
        }

        $this->clearFailedAttempts($identifierHash);
        session()->regenerate();

        SmartLogger::info('login_success')
            ->event('login_success')
            ->module('Auth')
            ->about($user)
            ->activityOnly()
            ->save();

        Event::dispatch(new LoginSucceeded($user, $data->identifier));

        return $user;
    }

    private function checkLockout(string $identifierHash): void
    {
        $lockoutKey = config('cache-keys.auth_login_lockout').$identifierHash;
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
    }

    private function checkAccountStatus(User $user, string $identifier): void
    {
        $apprentice = $user->asApprentice();

        if ($apprentice->isLocked()) {
            Event::dispatch(new LoginFailed($identifier, 'locked'));
            throw new RuntimeException(__('auth.blocked'));
        }

        if (! $apprentice->status()->allowsLogin()) {
            Event::dispatch(new LoginFailed($identifier, 'status_blocked'));
            throw new RuntimeException(__('auth.blocked'));
        }

        if ($apprentice->requiresSetup()) {
            Event::dispatch(new LoginFailed($identifier, 'setup_required'));
            throw new RuntimeException(__('auth.blocked'));
        }
    }

    private function handleFailedAttempt(string $identifierHash, string $identifier): void
    {
        $attemptsKey = config('cache-keys.auth_login_attempts').$identifierHash;
        $lockoutKey = config('cache-keys.auth_login_lockout').$identifierHash;

        $attempts = (int) Cache::get($attemptsKey, 0) + 1;
        Cache::put($attemptsKey, $attempts, now()->addHours(24));

        if ($attempts >= 10) {
            $durationSeconds = 10 * 2 ** ($attempts - 10);
            Cache::put($lockoutKey, now()->addSeconds($durationSeconds), now()->addSeconds($durationSeconds));
        }
    }

    private function clearFailedAttempts(string $identifierHash): void
    {
        Cache::forget(config('cache-keys.auth_login_attempts').$identifierHash);
        Cache::forget(config('cache-keys.auth_login_lockout').$identifierHash);
    }
}
