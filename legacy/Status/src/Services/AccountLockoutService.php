<?php

declare(strict_types=1);

namespace Modules\Status\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\Status\Enums\Status;
use Modules\User\Models\User;

class AccountLockoutService
{
    private const MAX_ATTEMPTS = 5;

    private const LOCKOUT_DURATION_MINUTES = 30;

    private const ATTEMPT_WINDOW_MINUTES = 15;

    public function __construct(private AccountAuditLogger $auditLogger) {}

    /**
     * Record a failed login attempt and potentially lock the account.
     * Uses exponential backoff: 5 attempts in 15 mins = 30-min lockout.
     */
    public function recordFailedAttempt(User $user, ?string $ipAddress = null): bool
    {
        // Protected accounts cannot be locked
        if ($user->isProtected()) {
            return false;
        }

        $cacheKey = $this->getAttemptKey($user);
        $attempts = Cache::get($cacheKey, 0);
        $newAttempts = $attempts + 1;

        // Record attempt in cache (window expires after ATTEMPT_WINDOW_MINUTES)
        Cache::put($cacheKey, $newAttempts, now()->addMinutes(self::ATTEMPT_WINDOW_MINUTES));

        // Log failed attempt
        $this->auditLogger->logFailedLogin($user, $ipAddress);

        // Check if lockout threshold exceeded
        if ($newAttempts >= self::MAX_ATTEMPTS) {
            $this->lockoutAccount($user, $newAttempts, $ipAddress);

            return true;
        }

        Log::info('Failed login attempt recorded', [
            'user_id' => $user->id,
            'attempts' => $newAttempts,
            'max_attempts' => self::MAX_ATTEMPTS,
        ]);

        return false;
    }

    /**
     * Lock account for security - transitions to RESTRICTED with auto-lift expiration.
     */
    private function lockoutAccount(User $user, int $attemptCount, ?string $ipAddress = null): void
    {
        // Create temporary restriction (not a permanent status change)
        $restriction = $user->restrictions()->create([
            'restriction_type' => 'login_lockout',
            'restriction_key' => 'failed_login_attempts',
            'restriction_value' => (string) $attemptCount,
            'reason' => "Automatic lockout after {$attemptCount} failed login attempts",
            'applied_by_user_id' => null, // System-applied
            'applied_at' => now(),
            'expires_at' => now()->addMinutes(self::LOCKOUT_DURATION_MINUTES),
            'is_active' => true,
            'metadata' => [
                'ip_address' => $ipAddress,
                'auto_lift' => true,
            ],
        ]);

        // Log lockout event
        $this->auditLogger->logAccountLockout($user, $attemptCount, $ipAddress);

        Log::warning('Account locked out due to failed attempts', [
            'user_id' => $user->id,
            'attempts' => $attemptCount,
            'lockout_duration_minutes' => self::LOCKOUT_DURATION_MINUTES,
            'ip_address' => $ipAddress,
        ]);
    }

    /**
     * Clear failed login attempts (call after successful login).
     */
    public function clearFailedAttempts(User $user): void
    {
        Cache::forget($this->getAttemptKey($user));

        // Also remove any active login lockout restrictions
        $user
            ->restrictions()
            ->where('restriction_type', 'login_lockout')
            ->where('is_active', true)
            ->update(['is_active' => false]);

        Log::info('Failed login attempts cleared', ['user_id' => $user->id]);
    }

    /**
     * Check if account is currently locked out.
     */
    public function isLockedOut(User $user): bool
    {
        // Check for active login lockout restriction
        return $user
            ->restrictions()
            ->where('restriction_type', 'login_lockout')
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->exists();
    }

    /**
     * Get remaining lockout time in minutes (0 if not locked out).
     */
    public function getRemainingLockoutMinutes(User $user): int
    {
        $restriction = $user
            ->restrictions()
            ->where('restriction_type', 'login_lockout')
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->first();

        if (! $restriction || ! $restriction->expires_at) {
            return 0;
        }

        return max(0, now()->diffInMinutes($restriction->expires_at, absolute: false));
    }

    /**
     * Manually unlock an account (admin action).
     */
    public function unlockAccount(User $user, User $unlockedBy, ?string $reason = null): void
    {
        $user
            ->restrictions()
            ->where('restriction_type', 'login_lockout')
            ->where('is_active', true)
            ->update([
                'is_active' => false,
                'reason' => $reason ?? 'Manually unlocked by admin',
            ]);

        Log::info('Account unlocked by admin', [
            'user_id' => $user->id,
            'unlocked_by' => $unlockedBy->id,
            'reason' => $reason,
        ]);
    }

    private function getAttemptKey(User $user): string
    {
        return "login_attempts:{$user->id}";
    }
}
