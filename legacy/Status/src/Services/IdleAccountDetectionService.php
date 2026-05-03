<?php

declare(strict_types=1);

namespace Modules\Status\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Modules\Status\Enums\Status;
use Modules\User\Models\User;

class IdleAccountDetectionService
{
    // NIST SP 800-63B defaults (configurable via config)
    private const IDLE_THRESHOLD_DAYS = 180; // Transition to INACTIVE

    private const ARCHIVE_THRESHOLD_DAYS = 365; // Transition to ARCHIVED

    private const GDPR_RETENTION_YEARS = 7; // Data purge date

    public function __construct(
        private StatusTransitionService $statusTransition,
        private AccountAuditLogger $auditLogger,
    ) {}

    /**
     * Detect and transition idle accounts.
     * Should be run daily via scheduled command.
     *
     * Transitions:
     * - VERIFIED → INACTIVE after 180 days
     * - INACTIVE → ARCHIVED after 365 days (1 year total)
     * - ARCHIVED records are flagged for deletion after 7 years
     */
    public function detectAndTransitionIdleAccounts(): array
    {
        $results = [
            'checked' => 0,
            'transitioned_to_inactive' => 0,
            'transitioned_to_archived' => 0,
            'errors' => [],
        ];

        // Find accounts that should be INACTIVE
        $inactiveAccounts = $this->findIdleAccounts(self::IDLE_THRESHOLD_DAYS);
        foreach ($inactiveAccounts as $user) {
            try {
                $this->statusTransition->transition(
                    user: $user,
                    newStatus: Status::INACTIVE,
                    reason: 'Automatic transition: Account idle for '.
                        self::IDLE_THRESHOLD_DAYS.
                        ' days',
                    ipAddress: null,
                    userAgent: 'System/IdleDetection',
                );
                $results['transitioned_to_inactive']++;
            } catch (\Exception $e) {
                $results['errors'][] = "Failed to transition user {$user->id}: {$e->getMessage()}";
            }
            $results['checked']++;
        }

        // Find accounts that should be ARCHIVED
        $archivedAccounts = $this->findIdleAccounts(self::ARCHIVE_THRESHOLD_DAYS);
        foreach ($archivedAccounts as $user) {
            $user->refresh(); // Reload to get latest status
            // Only archive if currently INACTIVE
            if ($user->getStatus() !== Status::INACTIVE) {
                continue;
            }

            try {
                $this->statusTransition->transition(
                    user: $user,
                    newStatus: Status::ARCHIVED,
                    reason: 'Automatic transition: Account inactive for '.
                        self::ARCHIVE_THRESHOLD_DAYS.
                        ' days',
                    ipAddress: null,
                    userAgent: 'System/IdleDetection',
                );
                $results['transitioned_to_archived']++;
            } catch (\Exception $e) {
                $results['errors'][] = "Failed to archive user {$user->id}: {$e->getMessage()}";
            }
            $results['checked']++;
        }

        Log::info('Idle account detection completed', $results);

        return $results;
    }

    /**
     * Find accounts idle for N days using Spatie status relations.
     * Uses last_activity_at column (updated on every request/action).
     */
    private function findIdleAccounts(int $dayThreshold): Collection
    {
        $cutoffDate = now()->subDays($dayThreshold);

        // Query using Spatie's status relationships
        return User::query()
            ->whereDoesntHave('statuses', function ($query) {
                $query->whereIn('name', [Status::PROTECTED->value, Status::ARCHIVED->value]);
            })
            ->where(function ($query) use ($cutoffDate) {
                $query
                    ->where('last_activity_at', '<', $cutoffDate)
                    ->orWhereNull('last_activity_at');
            })
            ->get();
    }

    /**
     * Get days until INACTIVE for a user.
     */
    public function daysUntilInactive(User $user): int
    {
        $currentStatus = $user->getStatus();
        if ($currentStatus === Status::INACTIVE || $currentStatus === Status::ARCHIVED) {
            return 0;
        }

        $lastActivity = $user->last_activity_at ?? $user->created_at;
        $daysIdle = $lastActivity->diffInDays(now());

        return max(0, self::IDLE_THRESHOLD_DAYS - $daysIdle);
    }

    /**
     * Get days until ARCHIVED for a user.
     */
    public function daysUntilArchived(User $user): int
    {
        $currentStatus = $user->getStatus();
        if ($currentStatus === Status::ARCHIVED) {
            return 0;
        }

        $lastActivity = $user->last_activity_at ?? $user->created_at;
        $daysIdle = $lastActivity->diffInDays(now());

        return max(0, self::ARCHIVE_THRESHOLD_DAYS - $daysIdle);
    }

    /**
     * Get GDPR data deletion deadline for a user.
     * Should delete archived account after 7 years.
     */
    public function getGdprDeletionDeadline(User $user): ?\DateTime
    {
        $currentStatus = $user->getStatus();
        if ($currentStatus !== Status::ARCHIVED) {
            return null;
        }

        // Find when account was archived from Spatie's statuses table
        $archivalStatus = $user
            ->statuses()
            ->where('name', Status::ARCHIVED->value)
            ->orderByDesc('created_at')
            ->first();

        if (! $archivalStatus) {
            return null;
        }

        return $archivalStatus->created_at->addYears(self::GDPR_RETENTION_YEARS);
    }

    /**
     * Get accounts eligible for GDPR deletion using Spatie relations.
     */
    public function findGdprDeletionEligible(): Collection
    {
        return User::whereHas('statuses', function ($query) {
            $cutoffDate = now()->subYears(self::GDPR_RETENTION_YEARS);
            $query->where('name', Status::ARCHIVED->value)->where('created_at', '<', $cutoffDate);
        })->get();
    }
}
