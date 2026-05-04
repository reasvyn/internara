<?php

declare(strict_types=1);

namespace App\Domain\User\Actions;

use App\Domain\User\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Checks if a user account has expired due to session timeout.
 *
 * S1 - Secure: Enforces session expiration policy.
 * S2 - Sustain: Configurable timeout with error handling.
 */
class CheckUserSessionExpiryAction
{
    private const CACHE_KEY_PREFIX = 'user.last_activity.';

    public function __construct(
        protected readonly int $timeoutMinutes = 120,
    ) {}

    /**
     * Check if the user's session has expired.
     *
     * Returns true if the session has expired, false otherwise.
     * Returns false when no activity is recorded (first session).
     */
    public function execute(User $user): bool
    {
        try {
            $lastActivity = Cache::get(self::CACHE_KEY_PREFIX.$user->id);

            if ($lastActivity === null) {
                return false;
            }

            return now()->diffInMinutes($lastActivity) >= $this->timeoutMinutes;
        } catch (\Throwable $e) {
            Log::warning('Failed to check session expiry', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Record user activity timestamp.
     */
    public function recordActivity(User $user): void
    {
        try {
            Cache::put(
                self::CACHE_KEY_PREFIX.$user->id,
                now(),
                now()->addMinutes($this->timeoutMinutes),
            );
        } catch (\Throwable $e) {
            Log::warning('Failed to record user activity', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
