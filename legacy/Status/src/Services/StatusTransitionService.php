<?php

declare(strict_types=1);

namespace Modules\Status\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Status\Enums\Status;
use Modules\Status\Notifications\AccountStatusChanged;
use Modules\User\Models\User;
use Spatie\ModelStatus\Models\Status as StatusModel;

class StatusTransitionService
{
    public function __construct(private AccountAuditLogger $auditLogger) {}

    /**
     * Attempt to transition a user's account status using Spatie's status system.
     * Validates rules, prevents invalid transitions, logs changes.
     *
     * @throws \InvalidArgumentException if transition not allowed
     * @throws \LogicException if user is protected
     */
    public function transition(
        User $user,
        Status $newStatus,
        ?string $reason = null,
        ?User $triggeredBy = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        ?array $metadata = null,
    ): StatusModel {
        // Prevent transitioning protected accounts (Super Admins)
        if ($user->isProtected()) {
            throw new \LogicException(
                'Protected accounts cannot be transitioned. Status is immutable.',
            );
        }

        // Get current status from Spatie
        $currentStatus = $user->getStatus();

        if (! $currentStatus) {
            throw new \LogicException('User has no current status set');
        }

        // Check if transition is valid
        if (! $currentStatus->canTransitionTo($newStatus)) {
            throw new \InvalidArgumentException(
                "Invalid transition: {$currentStatus->value} → {$newStatus->value}",
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
        ) {
            // Set new status using Spatie's API (creates status_histories record)
            $user->setStatus($newStatus->value, $reason);

            // Reload to get the new status model
            $statusModel = $user->latestStatus();

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
                $user->notify(
                    new AccountStatusChanged(
                        user: $user,
                        oldStatus: $currentStatus,
                        newStatus: $newStatus,
                        reason: $reason,
                        changedBy: $triggeredBy,
                    ),
                );
            } catch (\Exception $e) {
                Log::warning('Failed to send status change notification', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }

            Log::info('Account status transitioned', [
                'user_id' => $user->id,
                'old_status' => $currentStatus->value,
                'new_status' => $newStatus->value,
                'triggered_by' => $triggeredBy?->id ?? 'system',
            ]);

            return $statusModel;
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
        Status $newStatus,
        ?User $triggeredBy = null,
    ): void {
        // If no one triggered it, assume system action (allowed)
        if (! $triggeredBy) {
            return;
        }

        // Regular users can only change their own status (but limited transitions)
        if ($triggeredBy->role !== 'super_admin' && $triggeredBy->role !== 'admin') {
            if ($triggeredBy->id !== $user->id) {
                throw new \InvalidArgumentException(
                    'Users can only change their own account status',
                );
            }
            // Regular users cannot transition themselves to PROTECTED or VERIFIED
            if (\in_array($newStatus, [Status::PROTECTED, Status::VERIFIED], true)) {
                throw new \InvalidArgumentException(
                    "Users cannot self-transition to {$newStatus->value} status",
                );
            }
        }

        // Only Super Admins can set PROTECTED status
        if ($newStatus === Status::PROTECTED && $triggeredBy->role !== 'super_admin') {
            throw new \InvalidArgumentException('Only Super Admins can set PROTECTED status');
        }

        // Only Super Admin or higher can verify other admins
        if ($user->role === 'admin' && $newStatus === Status::VERIFIED) {
            if ($triggeredBy->role !== 'super_admin') {
                throw new \InvalidArgumentException('Only Super Admins can verify Admin accounts');
            }
        }
    }

    /**
     * Get valid next states for a user based on their current status.
     */
    public function getValidNextStates(User $user): array
    {
        if ($user->isProtected()) {
            return [];
        }

        $currentStatus = $user->getStatus();
        if (! $currentStatus) {
            return [];
        }

        return $currentStatus->validTransitions();
    }

    /**
     * Check if a specific transition is allowed.
     */
    public function canTransition(User $user, Status $newStatus): bool
    {
        try {
            if ($user->isProtected()) {
                return false;
            }

            $currentStatus = $user->getStatus();
            if (! $currentStatus) {
                return false;
            }

            return $currentStatus->canTransitionTo($newStatus);
        } catch (\Exception) {
            return false;
        }
    }
}
