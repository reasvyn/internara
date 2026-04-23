<?php

declare(strict_types=1);

namespace Modules\Status\Services;

use Modules\User\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * PasswordPolicyService
 *
 * Enforces role-based password policies for enterprise compliance:
 * - Super Admin: Password expires every 30 days
 * - Admin: Password expires every 60 days
 * - Standard users: Password expires every 90 days
 *
 * Features:
 * - Password history (prevent reuse of last 5 passwords)
 * - Expiration enforcement (force change on next login)
 * - Warning period (14 days before expiration)
 * - Change timestamp tracking for audit
 * - Complexity requirements (min 12 chars, uppercase, lowercase, number, symbol)
 */
class PasswordPolicyService
{
    private AccountAuditLogger $auditLogger;

    public function __construct(AccountAuditLogger $auditLogger)
    {
        $this->auditLogger = $auditLogger;
    }

    /**
     * Get password expiration period based on user role (days)
     *
     * @param User $user
     * @return int Days until password expires
     */
    public function getExpirationDays(User $user): int
    {
        return match ($user->getHighestRole()) {
            'super_admin' => (int) config('auth.password_policy.super_admin_expiry_days', 30),
            'admin' => (int) config('auth.password_policy.admin_expiry_days', 60),
            default => (int) config('auth.password_policy.standard_expiry_days', 90),
        };
    }

    /**
     * Check if user's password is expired
     *
     * @param User $user
     * @return bool
     */
    public function isExpired(User $user): bool
    {
        if (!$user->password_changed_at) {
            return true; // Never changed password
        }

        $expiryDays = $this->getExpirationDays($user);
        $expiresAt = $user->password_changed_at->addDays($expiryDays);

        return now()->isAfter($expiresAt);
    }

    /**
     * Check if password is expiring soon (within warning period)
     *
     * @param User $user
     * @return bool
     */
    public function isExpiringSoon(User $user): bool
    {
        if (!$user->password_changed_at) {
            return true;
        }

        $expiryDays = $this->getExpirationDays($user);
        $warningDays = (int) config('auth.password_policy.warning_days', 14);
        $expiresAt = $user->password_changed_at->addDays($expiryDays);
        $warningDate = $expiresAt->subDays($warningDays);

        return now()->isBetween($warningDate, $expiresAt);
    }

    /**
     * Get days until password expires
     *
     * @param User $user
     * @return int
     */
    public function getDaysUntilExpiry(User $user): int
    {
        if (!$user->password_changed_at) {
            return 0;
        }

        $expiryDays = $this->getExpirationDays($user);
        $expiresAt = $user->password_changed_at->addDays($expiryDays);

        return max(0, $expiresAt->diffInDays(now(), false));
    }

    /**
     * Validate new password against policy requirements
     *
     * @param string $password
     * @return array{valid: bool, errors: string[]}
     */
    public function validatePasswordComplexity(string $password): array
    {
        $errors = [];

        // Minimum length
        if (strlen($password) < 12) {
            $errors[] = 'Password must be at least 12 characters long';
        }

        // Uppercase required
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter';
        }

        // Lowercase required
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain at least one lowercase letter';
        }

        // Number required
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain at least one number';
        }

        // Symbol required
        if (!preg_match('/[!@#$%^&*()_+\-=\[\]{};:\'",.<>?\/?]/', $password)) {
            $errors[] = 'Password must contain at least one special character (!@#$%^&*)';
        }

        return [
            'valid' => count($errors) === 0,
            'errors' => $errors,
        ];
    }

    /**
     * Validate password against history (prevent reuse)
     *
     * @param User $user
     * @param string $password
     * @return bool True if password has NOT been used recently
     */
    public function validatePasswordHistory(User $user, string $password): bool
    {
        $historyLimit = (int) config('auth.password_policy.history_limit', 5);

        $recentPasswords = DB::table('password_history')
            ->where('user_id', $user->id)
            ->orderBy('changed_at', 'desc')
            ->limit($historyLimit)
            ->get('password_hash');

        foreach ($recentPasswords as $record) {
            if (Hash::check($password, $record->password_hash)) {
                return false; // Password was recently used
            }
        }

        return true;
    }

    /**
     * Update password and record in history
     *
     * @param User $user
     * @param string $newPassword
     * @return bool
     */
    public function updatePassword(User $user, string $newPassword): bool
    {
        return DB::transaction(function () use ($user, $newPassword) {
            // Store old password in history
            DB::table('password_history')->insert([
                'user_id' => $user->id,
                'password_hash' => $user->password, // Current hash before change
                'changed_at' => now(),
            ]);

            // Update user password
            $user->update([
                'password' => Hash::make($newPassword),
                'password_changed_at' => now(),
            ]);

            // Log password change
            $this->auditLogger->log(
                user: $user,
                event: 'password_changed',
                metadata: [
                    'changed_by' => auth()->id() === $user->id ? 'self' : 'admin',
                    'ip_address' => request()?->ip(),
                ]
            );

            return true;
        });
    }

    /**
     * Mark password reset as required (force change on next login)
     *
     * @param User $user
     * @param string $reason
     * @return void
     */
    public function requirePasswordReset(User $user, string $reason = 'admin_required'): void
    {
        $user->update([
            'password_reset_required_at' => now(),
            'metadata->password_reset_reason' => $reason,
        ]);

        $this->auditLogger->log(
            user: $user,
            event: 'password_reset_required',
            metadata: [
                'reason' => $reason,
                'triggered_by' => auth()->id(),
            ]
        );

        Log::info('Password reset required', [
            'user_id' => $user->id,
            'reason' => $reason,
        ]);
    }

    /**
     * Check if user must reset password on next login
     *
     * @param User $user
     * @return bool
     */
    public function mustResetPassword(User $user): bool
    {
        return $user->password_reset_required_at !== null
            && $user->password_reset_required_at->isBefore(now());
    }

    /**
     * Clean up old password history (older than 2 years)
     *
     * @return int Number of records deleted
     */
    public function cleanupPasswordHistory(): int
    {
        return DB::table('password_history')
            ->where('changed_at', '<', now()->subYears(2))
            ->delete();
    }

    /**
     * Check if password was used recently (in last 5 passwords)
     *
     * @param User $user
     * @param string $password
     * @return bool True if password was used recently
     */
    public function wasPasswordUsedRecently(User $user, string $password): bool
    {
        return !$this->validatePasswordHistory($user, $password);
    }

    /**
     * Record a password change in history
     *
     * @param User $user
     * @param string $newPassword
     * @return void
     */
    public function recordPasswordChange(User $user, string $newPassword): void
    {
        DB::table('password_history')->insert([
            'user_id' => $user->id,
            'password_hash' => Hash::make($newPassword),
            'changed_at' => now(),
        ]);

        // Keep only last 5 passwords
        $historyLimit = (int) config('auth.password_policy.history_limit', 5);
        DB::table('password_history')
            ->where('user_id', $user->id)
            ->orderBy('changed_at', 'desc')
            ->offset($historyLimit)
            ->delete();
    }
}
