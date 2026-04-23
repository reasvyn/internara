<?php

declare(strict_types=1);

namespace Modules\Status\Services;

use Illuminate\Support\Facades\Log;
use Modules\Status\Enums\AccountStatus;
use Modules\User\Models\User;

class AccountAuditLogger
{
    /**
     * Log account status changes for compliance and audit purposes.
     * Implements GDPR-compliant audit trail.
     */
    public function logStatusChange(
        User $user,
        AccountStatus $oldStatus,
        AccountStatus $newStatus,
        ?string $reason = null,
        ?User $triggeredBy = null,
        ?string $ipAddress = null,
    ): void {
        $context = [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'user_role' => $user->role,
            'old_status' => $oldStatus->value,
            'new_status' => $newStatus->value,
            'reason' => $reason,
            'triggered_by_user_id' => $triggeredBy?->id,
            'triggered_by_role' => $triggeredBy?->role,
            'triggered_by_email' => $triggeredBy?->email,
            'ip_address' => $ipAddress,
            'timestamp' => now()->toIso8601String(),
        ];

        // Log to application log
        Log::channel('audit')->info(
            "Account status changed: {$user->email} ({$oldStatus->value} → {$newStatus->value})",
            $context,
        );

        // Log specific status transitions of interest
        match ($newStatus) {
            AccountStatus::PROTECTED => $this->logProtectedStatusAssignment($user, $triggeredBy),
            AccountStatus::VERIFIED => $this->logVerification($user, $triggeredBy),
            AccountStatus::RESTRICTED => $this->logRestriction($user, $triggeredBy),
            AccountStatus::SUSPENDED => $this->logSuspension($user, $reason, $triggeredBy),
            AccountStatus::ARCHIVED => $this->logArchival($user, $triggeredBy),
            default => null,
        };
    }

    private function logProtectedStatusAssignment(User $user, ?User $triggeredBy): void
    {
        Log::channel('audit')->warning(
            "🔒 PROTECTED status assigned to {$user->email} by {$triggeredBy?->email}",
            [
                'user_id' => $user->id,
                'protected_by' => $triggeredBy?->id,
                'action' => 'protected_status_assigned',
            ],
        );
    }

    private function logVerification(User $user, ?User $triggeredBy): void
    {
        Log::channel('audit')->info(
            "✅ Account verified: {$user->email}" . ($triggeredBy ? " by {$triggeredBy->email}" : ""),
            [
                'user_id' => $user->id,
                'verified_by' => $triggeredBy?->id,
                'action' => 'account_verified',
            ],
        );
    }

    private function logRestriction(User $user, ?User $triggeredBy): void
    {
        Log::channel('audit')->notice(
            "⚠️  Account restricted: {$user->email}" . ($triggeredBy ? " by {$triggeredBy->email}" : ""),
            [
                'user_id' => $user->id,
                'restricted_by' => $triggeredBy?->id,
                'action' => 'account_restricted',
            ],
        );
    }

    private function logSuspension(User $user, ?string $reason, ?User $triggeredBy): void
    {
        Log::channel('audit')->warning(
            "🚫 Account suspended: {$user->email}" . ($triggeredBy ? " by {$triggeredBy->email}" : ""),
            [
                'user_id' => $user->id,
                'suspended_by' => $triggeredBy?->id,
                'reason' => $reason,
                'action' => 'account_suspended',
            ],
        );
    }

    private function logArchival(User $user, ?User $triggeredBy): void
    {
        Log::channel('audit')->info(
            "📦 Account archived: {$user->email}" . ($triggeredBy ? " by {$triggeredBy->email}" : ""),
            [
                'user_id' => $user->id,
                'archived_by' => $triggeredBy?->id,
                'action' => 'account_archived',
            ],
        );
    }

    /**
     * Log failed login attempts for lockout mechanism.
     */
    public function logFailedLogin(User $user, ?string $ipAddress = null): void
    {
        Log::channel('audit')->notice(
            "Failed login attempt: {$user->email}",
            [
                'user_id' => $user->id,
                'ip_address' => $ipAddress,
                'action' => 'failed_login_attempt',
            ],
        );
    }

    /**
     * Log account lockout.
     */
    public function logAccountLockout(User $user, int $attemptCount, ?string $ipAddress = null): void
    {
        Log::channel('audit')->warning(
            "⛔ Account locked out: {$user->email} after {$attemptCount} failed attempts",
            [
                'user_id' => $user->id,
                'failed_attempts' => $attemptCount,
                'ip_address' => $ipAddress,
                'action' => 'account_locked_out',
            ],
        );
    }

    /**
     * Log session expiration events.
     */
    public function logSessionExpired(User $user, string $reason, ?string $ipAddress = null): void
    {
        Log::channel('audit')->info(
            "🔓 Session expired: {$user->email}",
            [
                'user_id' => $user->id,
                'reason' => $reason,
                'ip_address' => $ipAddress,
                'action' => 'session_expired',
            ],
        );
    }

    /**
     * Log successful login.
     */
    public function logSuccessfulLogin(User $user, ?string $ipAddress = null): void
    {
        Log::channel('audit')->info(
            "✅ Successful login: {$user->email}",
            [
                'user_id' => $user->id,
                'ip_address' => $ipAddress,
                'action' => 'successful_login',
            ],
        );
    }
}
