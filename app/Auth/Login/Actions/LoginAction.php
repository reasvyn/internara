<?php

declare(strict_types=1);

namespace App\Auth\Login\Actions;

use App\Auth\Login\Data\LoginData;
use App\Auth\Login\Events\LoginFailed;
use App\Auth\Login\Events\LoginSucceeded;
use App\Core\Actions\BaseCommandAction;
use App\Core\Exceptions\RejectedException;
use App\User\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

final class LoginAction extends BaseCommandAction
{
    public function execute(
        string $identifier,
        string $password,
        bool $remember = false,
    ): Authenticatable {
        return $this->transaction(function () use ($identifier, $password, $remember) {
            $data = new LoginData(identifier: $identifier, password: $password, remember: $remember);
            $identifierHash = hash('crc32b', $data->identifier);

            $this->checkLockout($identifierHash);

            $loginField = filter_var($data->identifier, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
            $user = User::where($loginField, $data->identifier)->first();

            if ($user === null) {
                $this->handleFailedAttempt($identifierHash, $data->identifier);
                event(new LoginFailed($data->identifier, 'user_not_found'));
                throw new RejectedException(__('auth.failed'));
            }

            $this->checkAccountStatus($user, $data->identifier);

            if (
                ! Auth::attempt(
                    [$loginField => $data->identifier, 'password' => $data->password],
                    $data->remember,
                )
            ) {
                $this->handleFailedAttempt($identifierHash, $data->identifier);
                event(new LoginFailed($data->identifier, 'invalid_password'));
                throw new RejectedException(__('auth.failed'));
            }

            $this->clearFailedAttempts($identifierHash);
            session()->regenerate();

            $this->log('login_success', $user, ['identifier' => $data->identifier]);

            event(new LoginSucceeded($user, $data->identifier));

            return $user;
        });
    }

    private function checkLockout(string $identifierHash): void
    {
        $lockoutKey = config('cache-keys.auth_login_lockout').$identifierHash;
        $lockoutUntil = Cache::get($lockoutKey);

        if ($lockoutUntil !== null) {
            $lockoutTime = Carbon::parse($lockoutUntil);
            if (now()->lt($lockoutTime)) {
                $seconds = (int) ceil(now()->diffInSeconds($lockoutTime));
                throw new RejectedException(
                    __('auth.throttle', ['seconds' => $seconds]) ??
                        "Too many login attempts. Please try again in {$seconds} seconds.",
                );
            }
        }
    }

    private function checkAccountStatus(User $user, string $identifier): void
    {
        $apprentice = $user->asApprentice();

        if ($apprentice->isLocked()) {
            event(new LoginFailed($identifier, 'locked'));
            throw new RejectedException(__('auth.blocked'));
        }

        if (! $apprentice->status()->allowsLogin()) {
            event(new LoginFailed($identifier, 'status_blocked'));
            throw new RejectedException(__('auth.blocked'));
        }

        if ($apprentice->requiresSetup()) {
            event(new LoginFailed($identifier, 'setup_required'));
            throw new RejectedException(__('auth.blocked'));
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
            Cache::put(
                $lockoutKey,
                now()->addSeconds($durationSeconds),
                now()->addSeconds($durationSeconds),
            );
        }
    }

    private function clearFailedAttempts(string $identifierHash): void
    {
        Cache::forget(config('cache-keys.auth_login_attempts').$identifierHash);
        Cache::forget(config('cache-keys.auth_login_lockout').$identifierHash);
    }
}
