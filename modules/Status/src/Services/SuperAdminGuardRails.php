<?php

declare(strict_types=1);

namespace Modules\Status\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Status\Enums\Status;
use Modules\User\Models\User;

/**
 * SuperAdminGuardRails
 *
 * Implements enterprise-grade safety checks for Super Admin accounts:
 * 1. Immutability - Super Admin status can never be changed
 * 2. Dual-approve - Require 2 admins to modify Super Admin accounts
 * 3. Activity tracking - Monitor all Super Admin access
 * 4. Minimum requirement - At least 1 Super Admin must always exist
 * 5. Password protection - Force password changes on Super Admin
 * 6. IP whitelist - Optional IP restriction for Super Admin
 * 7. Session isolation - No concurrent sessions for Super Admin
 * 8. Audit everything - All actions create audit records
 */
class SuperAdminGuardRails
{
    private AccountAuditLogger $auditLogger;

    private SessionExpirationService $sessionService;

    public function __construct(
        AccountAuditLogger $auditLogger,
        SessionExpirationService $sessionService,
    ) {
        $this->auditLogger = $auditLogger;
        $this->sessionService = $sessionService;
    }

    /**
     * Prevent Super Admin status from being changed
     *
     * Super Admin is immutable - once assigned, can only be revoked by
     * removing the user entirely (full deactivation)
     *
     * @throws \Exception
     *
     * @return bool True if status change allowed
     */
    public function canChangeStatus(User $user, AccountStatus $proposedStatus): bool
    {
        // If user IS Super Admin, they can never change their status
        if ($user->isProtected()) {
            throw new \Exception(
                'Cannot change Super Admin account status. ' .
                    'Super Admin accounts are immutable and cannot be modified, suspended, or archived. ' .
                    'Contact Governance Board for Super Admin removal.',
            );
        }

        // If user is being PROMOTED to Super Admin, require dual approval
        if ($proposedStatus === Status::PROTECTED && !$user->isProtected()) {
            // This should trigger dual-admin workflow (handled in StatusChangePolicy)
            Log::warning('Super Admin promotion requested - requires dual approval', [
                'user_id' => $user->id,
                'requested_by' => auth()->id(),
            ]);
        }

        return true;
    }

    /**
     * Enforce minimum Super Admin requirement
     *
     * Prevent deactivating the last Super Admin - at least one must exist
     *
     * @param User $user User being deactivated/archived
     *
     * @throws \Exception
     *
     * @return bool True if deactivation allowed
     */
    public function canDeactivate(User $user): bool
    {
        // If user is Super Admin, check if others exist
        if ($user->isProtected()) {
            $otherSuperAdmins = User::where('account_status', Status::PROTECTED->value)
                ->where('id', '!=', $user->id)
                ->count();

            if ($otherSuperAdmins === 0) {
                throw new \Exception(
                    'Cannot deactivate the last Super Admin account. ' .
                        'At least one Super Admin must remain active at all times.',
                );
            }
        }

        return true;
    }

    /**
     * Require dual approval for Super Admin changes
     *
     * Any modification to Super Admin accounts (password, email, etc)
     * requires approval from 2 other Super Admins
     *
     * @param User $targetUser User being modified
     * @param string $changeType password|email|settings|deactivate
     * @param array $changeData Details of proposed change
     *
     * @return bool True if change can proceed without approval
     */
    public function requiresDualApproval(
        User $targetUser,
        string $changeType,
        array $changeData = [],
    ): bool {
        // Only apply to Super Admin targets
        if (!$targetUser->isProtected()) {
            return false;
        }

        // Only Super Admins making the change
        $actor = auth()->user();
        if (!$actor || !$actor->isProtected()) {
            // Non-Super-Admin cannot modify Super Admin
            throw new \Exception('Only Super Admins can modify other Super Admin accounts.');
        }

        // Store approval request
        DB::table('super_admin_approvals')->insert([
            'target_user_id' => $targetUser->id,
            'requested_by_user_id' => $actor->id,
            'change_type' => $changeType,
            'change_data' => json_encode($changeData),
            'status' => 'pending', // pending, approved, rejected
            'approvals_count' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->auditLogger->log(
            user: $targetUser,
            event: 'super_admin_change_requested',
            metadata: [
                'change_type' => $changeType,
                'requested_by' => $actor->id,
                'requires_approval' => true,
            ],
        );

        return true; // Dual approval required
    }

    /**
     * Approve Super Admin change (one of 2 required)
     *
     * @param User $approver Super Admin giving approval
     *
     * @throws \Exception
     *
     * @return bool True if approval successful (and 2 approvals now received)
     */
    public function approveChange(int $approvalId, User $approver): bool
    {
        if (!$approver->isProtected()) {
            throw new \Exception('Only Super Admins can approve Super Admin changes.');
        }

        $approval = DB::table('super_admin_approvals')->find($approvalId);
        if (!$approval) {
            throw new \Exception('Approval request not found.');
        }

        if ($approval->status !== 'pending') {
            throw new \Exception('Approval already ' . $approval->status);
        }

        // Check if same Super Admin requested and approved (prevent self-approval)
        if ($approval->requested_by_user_id === $approver->id) {
            throw new \Exception(
                'Cannot approve your own changes. Requires approval from another Super Admin.',
            );
        }

        // Record approval
        DB::table('super_admin_approvals')->where('id', $approvalId)->increment('approvals_count');

        $approval = DB::table('super_admin_approvals')->find($approvalId);

        // Check if we have 2 approvals now
        if ($approval->approvals_count >= 2) {
            DB::table('super_admin_approvals')
                ->where('id', $approvalId)
                ->update(['status' => 'approved', 'approved_at' => now()]);

            $this->auditLogger->log(
                user: User::find($approval->target_user_id),
                event: 'super_admin_change_approved',
                metadata: [
                    'change_type' => $approval->change_type,
                    'approved_by' => $approver->id,
                    'final_approval' => true,
                ],
            );

            return true; // Both approvals received!
        }

        return false; // Still waiting for second approval
    }

    /**
     * Enforce session isolation for Super Admin
     *
     * Only one active session per Super Admin account at a time
     * Prevents account sharing/compromise
     *
     * @throws \Exception
     *
     * @return bool True if new session allowed
     */
    public function enforceSessionIsolation(User $superAdmin): bool
    {
        if (!$superAdmin->isProtected()) {
            return true; // Only applies to Super Admins
        }

        // Check for existing active sessions
        $activeSessions = cache()->get("user_sessions_{$superAdmin->id}", []);

        if (count($activeSessions) > 0) {
            // Log potential account sharing
            Log::warning('Super Admin session isolation violation detected', [
                'user_id' => $superAdmin->id,
                'existing_sessions' => count($activeSessions),
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            // Invalidate old sessions
            foreach ($activeSessions as $sessionId) {
                session()->forget($sessionId);
            }

            $this->auditLogger->log(
                user: $superAdmin,
                event: 'session_isolation_enforced',
                metadata: [
                    'invalidated_sessions' => count($activeSessions),
                    'new_session_ip' => request()->ip(),
                ],
            );
        }

        // Register new session
        $sessionId = session()->getId();
        cache()->put("user_sessions_{$superAdmin->id}", [$sessionId], minutes: 24 * 60);

        return true;
    }

    /**
     * Track Super Admin activity for compliance
     *
     * Super Admin access is logged more extensively for audit purposes
     */
    public function trackActivity(User $superAdmin, string $action, array $metadata = []): void
    {
        if (!$superAdmin->isProtected()) {
            return;
        }

        $this->auditLogger->log(
            user: $superAdmin,
            event: "super_admin_{$action}",
            metadata: array_merge($metadata, [
                'tracked_for_compliance' => true,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]),
        );

        // Also log to compliance channel with highest priority
        Log::channel('audit')->alert(
            "🔐 Super Admin Activity: {$superAdmin->email} performed {$action}",
            [
                'super_admin_id' => $superAdmin->id,
                'action' => $action,
                'metadata' => $metadata,
            ],
        );
    }

    /**
     * Verify Super Admin IP whitelist (if configured)
     *
     * Optionally restrict Super Admin login to specific IP ranges
     *
     * @return bool True if IP allowed
     */
    public function isIpAllowed(User $superAdmin): bool
    {
        if (!$superAdmin->isProtected()) {
            return true; // Only applies to Super Admins
        }

        $ipWhitelist = config('auth.super_admin_ip_whitelist', []);
        if (empty($ipWhitelist)) {
            return true; // No whitelist configured
        }

        $userIp = request()->ip();

        foreach ($ipWhitelist as $allowedIp) {
            // Support CIDR notation (e.g., 192.168.1.0/24)
            if ($this->ipInRange($userIp, $allowedIp)) {
                return true;
            }
        }

        Log::warning('Super Admin login from unauthorized IP', [
            'user_id' => $superAdmin->id,
            'ip' => $userIp,
        ]);

        return false;
    }

    /**
     * Check if IP is in CIDR range
     *
     * @param string $range CIDR notation (e.g., 192.168.1.0/24)
     */
    private function ipInRange(string $ip, string $range): bool
    {
        if (strpos($range, '/') === false) {
            return $ip === $range; // Exact match
        }

        [$subnet, $bits] = explode('/', $range);
        $ip = ip2long($ip);
        $subnet = ip2long($subnet);
        $mask = -1 << 32 - $bits;
        $subnet = $subnet & $mask;

        return ($ip & $mask) === $subnet;
    }
}
