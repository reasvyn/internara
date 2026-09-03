<?php

declare(strict_types=1);

namespace App\Modules\Auth\Domain\Login\Actions;

use App\Modules\Auth\Domain\Login\Data\LoginData;
use App\Modules\Auth\Domain\Login\Events\LoginFailed;
use App\Modules\Auth\Domain\Login\Events\LoginSucceeded;
use App\Modules\Core\Actions\BaseCommandAction;
use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\User\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

final class LoginAction extends BaseCommandAction
{
    public function execute(LoginData $data): Authenticatable
    {
        return $this->transaction(function () use ($data) {
            $identifierHash = hash('crc32b', $data->identifier);

            // FR-LT7 timing: read lockout cache BEFORE user lookup to prevent enumeration.
            // Enforcement is deferred until we know if the user is superadmin (NFR-S10 / G5).
            $lockoutUntil = Cache::get(config('cache-keys.auth_login_lockout').$identifierHash);

            $loginField = filter_var($data->identifier, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
            $user = User::where($loginField, $data->identifier)->first();

            if ($user === null) {
                $this->handleFailedAttempt($identifierHash, $data->identifier, null);
                event(new LoginFailed($data->identifier, 'user_not_found'));
                throw new RejectedException(__('auth.failed'));
            }

            $isSuperAdmin = $user->hasRole('super_admin');

            if (! $isSuperAdmin) {
                $this->enforceLockout($lockoutUntil);
                $this->checkAccountStatus($user, $data->identifier);
            }

            if (
                ! Auth::attempt(
                    [$loginField => $data->identifier, 'password' => $data->password],
                    $data->remember,
                )
            ) {
                $this->handleFailedAttempt($identifierHash, $data->identifier, $user);
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

    private function enforceLockout(mixed $lockoutUntil): void
    {
        if ($lockoutUntil === null) {
            return;
        }

        $lockoutTime = Carbon::parse($lockoutUntil);
        if (now()->lt($lockoutTime)) {
            $seconds = (int) ceil(now()->diffInSeconds($lockoutTime));
            throw new RejectedException(
                __('auth.throttle', ['seconds' => $seconds]) ??
                    "Too many login attempts. Please try again in {$seconds} seconds.",
            );
        }
    }

    /**
     * @deprecated Use enforceLockout() — kept for backward compatibility, delegates to enforceLockout().
     */
    private function checkLockout(string $identifierHash): void
    {
        $this->enforceLockout(Cache::get(config('cache-keys.auth_login_lockout').$identifierHash));
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

    private function handleFailedAttempt(string $identifierHash, string $identifier, ?User $user = null): void
    {
        // NFR-S10 / G5: superadmin is non-lockable — never create lockout entries for it.
        if ($user !== null && $user->hasRole('super_admin')) {
            return;
        }

        // For user_not_found ($user === null) we cannot know if the identifier was superadmin,
        // so we keep counting — enumeration-safe and spec-compliant (FR-LT7).
        // If the identifier was a superadmin but not found via the resolved field,
        // the count is harmless because enforcement is skipped on next successful lookup.
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
