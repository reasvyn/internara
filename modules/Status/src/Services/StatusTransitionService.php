<?php

declare(strict_types=1);

namespace Modules\Status\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Status\Enums\AccountStatus;
use Modules\Status\Models\AccountStatusHistory;
use Modules\Status\Notifications\AccountStatusChanged;
use Modules\User\Models\User;

class StatusTransitionService
{
    public function __construct(
        private AccountAuditLogger $auditLogger,
    ) {}

    /**
     * Attempt to transition a user's account status.
     * Validates rules, prevents invalid transitions, logs changes.
     *
     * @throws \InvalidArgumentException if transition not allowed
     * @throws \LogicException if user is protected
     */
    public function transition(
        User $user,
        AccountStatus $newStatus,
        ?string $reason = null,
        ?User $triggeredBy = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        ?array $metadata = null,
    ): AccountStatusHistory {
        // Prevent transitioning protected accounts (Super Admins)
        if ($user->isProtected()) {
            throw new \LogicException("Protected accounts cannot be transitioned. Status is immutable.");
        }

        $currentStatus = $user->account_status;

        // Check if transition is valid
        if (!$currentStatus->canTransitionTo($newStatus)) {
            throw new \InvalidArgumentException(
                "Invalid transition: {$currentStatus->value} → {$newStatus->value}"
            );
        }

        // Check role-based restrictions
        $this->validateRoleBasedRules($user, $newStatus, $triggeredBy);

        // Execute transition in transaction
        return DB::transaction(function () use (
            $user,
            $newStatus,
            $currentStatus,
            $reason,
            $triggeredBy,
            $ipAddress,
            $userAgent,
            $metadata,
        ) {
            // Update user status
            $user->account_status = $newStatus;
            $user->save();

            // Create audit trail record
            $history = AccountStatusHistory::create([
                'user_id' => $user->id,
                'old_status' => $currentStatus->value,
                'new_status' => $newStatus->value,
                'reason' => $reason,
                'triggered_by_user_id' => $triggeredBy?->id,
                'triggered_by_role' => $triggeredBy?->role,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'metadata' => $metadata,
            ]);

            // Log to audit system
            $this->auditLogger->logStatusChange(
                user: $user,
                oldStatus: $currentStatus,
                newStatus: $newStatus,
                reason: $reason,
                triggeredBy: $triggeredBy,
                ipAddress: $ipAddress,
            );

            // Dispatch event for any listeners (emails, webhooks, etc.)
            event('account.status.changed', [
                'user_id' => $user->id,
                'old_status' => $currentStatus->value,
                'new_status' => $newStatus->value,
                'triggered_by_user_id' => $triggeredBy?->id,
            ]);

            // Send notification to user about status change
            try {
                $user->notify(new AccountStatusChanged(
                    user: $user,
                    oldStatus: $currentStatus,
                    newStatus: $newStatus,
                    reason: $reason,
                    changedBy: $triggeredBy,
                ));
            } catch (\Exception $e) {
                Log::warning("Failed to send status change notification", [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }

            Log::info("Account status transitioned", [
                'user_id' => $user->id,
                'old_status' => $currentStatus->value,
                'new_status' => $newStatus->value,
                'triggered_by' => $triggeredBy?->id ?? 'system',
            ]);

            return $history;
        });
    }

    /**
     * Validate role-based transition rules.
     * - Super Admin: Only Super Admin can transition Admins to VERIFIED
     * - Admin accounts: Cannot be transitioned to certain states by regular users
     * - Standard users: Full transition flexibility
     */
    private function validateRoleBasedRules(
        User $user,
        AccountStatus $newStatus,
        ?User $triggeredBy = null,
    ): void {
        // If no one triggered it, assume system action (allowed)
        if (!$triggeredBy) {
            return;
        }

        // Regular users can only change their own status (but limited transitions)
        if ($triggeredBy->role !== 'super_admin' && $triggeredBy->role !== 'admin') {
            if ($triggeredBy->id !== $user->id) {
                throw new \InvalidArgumentException(
                    "Users can only change their own account status"
                );
            }
            // Regular users cannot transition themselves to PROTECTED or VERIFIED
            if (\in_array($newStatus, [AccountStatus::PROTECTED, AccountStatus::VERIFIED], true)) {
                throw new \InvalidArgumentException(
                    "Users cannot self-transition to {$newStatus->value} status"
                );
            }
        }

        // Only Super Admins can set PROTECTED status
        if ($newStatus === AccountStatus::PROTECTED && $triggeredBy->role !== 'super_admin') {
            throw new \InvalidArgumentException(
                "Only Super Admins can set PROTECTED status"
            );
        }

        // Only Super Admin or higher can verify other admins
        if ($user->role === 'admin' && $newStatus === AccountStatus::VERIFIED) {
            if ($triggeredBy->role !== 'super_admin') {
                throw new \InvalidArgumentException(
                    "Only Super Admins can verify Admin accounts"
                );
            }
        }
    }

    /**
     * Get valid next states for a user.
     */
    public function getValidNextStates(User $user): array
    {
        if ($user->isProtected()) {
            return [];
        }

        return $user->account_status->validTransitions();
    }

    /**
     * Check if a specific transition is allowed.
     */
    public function canTransition(User $user, AccountStatus $newStatus): bool
    {
        try {
            if ($user->isProtected()) {
                return false;
            }
            return $user->account_status->canTransitionTo($newStatus);
        } catch (\Exception) {
            return false;
        }
    }
}
