<?php

declare(strict_types=1);

namespace Modules\Status\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\User\Models\User;

class SessionExpirationService
{
    /**
     * Role-based session timeout durations (in minutes).
     * Following best practices: Admin roles get shorter timeouts for security.
     */
    private const SESSION_TIMEOUTS = [
        'super_admin' => 12 * 60,  // 12 hours
        'admin' => 12 * 60,         // 12 hours
        'teacher' => 24 * 60,       // 24 hours
        'supervisor' => 24 * 60,    // 24 hours
        'student' => 24 * 60,       // 24 hours
    ];

    private const INACTIVITY_WARNING_MINUTES = 2;  // Warn 2 min before expiry

    public function __construct(
        private AccountAuditLogger $auditLogger,
    ) {}

    /**
     * Record session start for a user.
     */
    public function recordSessionStart(User $user, string $sessionId, ?string $ipAddress = null): void
    {
        $timeout = self::SESSION_TIMEOUTS[$user->role] ?? 24 * 60;

        Cache::put(
            key: "session:{$sessionId}:user_id",
            value: $user->id,
            minutes: $timeout + 5  // Extend cache 5 min past actual timeout
        );

        Cache::put(
            key: "session:{$sessionId}:started_at",
            value: now()->toIso8601String(),
            minutes: $timeout + 5
        );

        Cache::put(
            key: "session:{$sessionId}:last_activity",
            value: now()->toIso8601String(),
            minutes: $timeout + 5
        );

        Cache::put(
            key: "session:{$sessionId}:ip_address",
            value: $ipAddress,
            minutes: $timeout + 5
        );

        Log::info("Session started", [
            'user_id' => $user->id,
            'session_id' => $sessionId,
            'role' => $user->role,
            'timeout_minutes' => $timeout,
            'ip_address' => $ipAddress,
        ]);
    }

    /**
     * Update last activity timestamp (call on each request).
     */
    public function updateLastActivity(string $sessionId): void
    {
        $timeout = $this->getSessionTimeout($sessionId);
        if ($timeout === null) {
            return;  // Session already expired
        }

        Cache::put(
            key: "session:{$sessionId}:last_activity",
            value: now()->toIso8601String(),
            minutes: $timeout + 5
        );
    }

    /**
     * Check if session has expired.
     */
    public function isExpired(string $sessionId): bool
    {
        $startedAt = Cache::get("session:{$sessionId}:started_at");
        if (!$startedAt) {
            return true;  // No session data = expired
        }

        $timeout = $this->getSessionTimeout($sessionId);
        if ($timeout === null) {
            return true;
        }

        $expiresAt = \Carbon\Carbon::parse($startedAt)->addMinutes($timeout);
        return now()->greaterThan($expiresAt);
    }

    /**
     * Get remaining session time in minutes (0 or negative if expired).
     */
    public function getRemainingMinutes(string $sessionId): int
    {
        $startedAt = Cache::get("session:{$sessionId}:started_at");
        if (!$startedAt) {
            return 0;
        }

        $timeout = $this->getSessionTimeout($sessionId);
        if ($timeout === null) {
            return 0;
        }

        $expiresAt = \Carbon\Carbon::parse($startedAt)->addMinutes($timeout);
        return max(0, now()->diffInMinutes($expiresAt, absolute: false));
    }

    /**
     * Check if session is approaching expiration (should show warning).
     */
    public function isApproachingExpiration(string $sessionId): bool
    {
        $remaining = $this->getRemainingMinutes($sessionId);
        return $remaining > 0 && $remaining <= self::INACTIVITY_WARNING_MINUTES;
    }

    /**
     * Invalidate/logout a session.
     */
    public function invalidateSession(string $sessionId, User $user, ?string $reason = null): void
    {
        $ipAddress = Cache::get("session:{$sessionId}:ip_address");

        // Remove all session data
        Cache::forget("session:{$sessionId}:user_id");
        Cache::forget("session:{$sessionId}:started_at");
        Cache::forget("session:{$sessionId}:last_activity");
        Cache::forget("session:{$sessionId}:ip_address");

        // Log session termination
        $this->auditLogger->logSessionExpired($user, $reason ?? "Manual logout", $ipAddress);

        Log::info("Session invalidated", [
            'user_id' => $user->id,
            'session_id' => $sessionId,
            'reason' => $reason,
            'ip_address' => $ipAddress,
        ]);
    }

    /**
     * Get timeout duration for a session (in minutes).
     */
    private function getSessionTimeout(string $sessionId): ?int
    {
        $userId = Cache::get("session:{$sessionId}:user_id");
        if (!$userId) {
            return null;
        }

        $user = User::find($userId);
        if (!$user) {
            return null;
        }

        return self::SESSION_TIMEOUTS[$user->role] ?? 24 * 60;
    }

    /**
     * Get session timeout for a specific role.
     */
    public static function getTimeoutForRole(string $role): int
    {
        return self::SESSION_TIMEOUTS[$role] ?? 24 * 60;
    }

    /**
     * Get all configured timeouts.
     */
    public static function getAllTimeouts(): array
    {
        return self::SESSION_TIMEOUTS;
    }
}
