<?php

declare(strict_types=1);

namespace Modules\Status\Services\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Status\Enums\Status;
use Modules\Status\Services\AccountAuditLogger;
use Modules\Status\Services\IdleAccountDetectionService;
use Modules\Status\Services\StatusTransitionService;
use Modules\User\Models\User;

/**
 * DetectIdleAccountsJob
 *
 * Scheduled daily to detect and transition accounts based on inactivity:
 * - 180 days idle → INACTIVE (user warned, can still login)
 * - 365 days idle → ARCHIVED (requires admin intervention to reactivate)
 * - 7 years idle → PURGED (GDPR right to be forgotten, anonymize data)
 *
 * Should be scheduled in console/Kernel.php:
 *   $schedule->job(new DetectIdleAccountsJob)->daily();
 */
class DetectIdleAccountsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private IdleAccountDetectionService $idleDetectionService;

    private StatusTransitionService $transitionService;

    private AccountAuditLogger $auditLogger;

    public function __construct()
    {
        $this->idleDetectionService = app(IdleAccountDetectionService::class);
        $this->transitionService = app(StatusTransitionService::class);
        $this->auditLogger = app(AccountAuditLogger::class);
    }

    public function handle(): void
    {
        try {
            Log::info('Starting idle account detection job');

            // Process accounts approaching INACTIVE (180d)
            $this->processApproachingInactive();

            // Process accounts becoming ARCHIVED (365d)
            $this->processBecomingArchived();

            // Process accounts for GDPR purge (7 years)
            $this->processGdprPurge();

            Log::info('Idle account detection job completed successfully');
        } catch (\Exception $e) {
            Log::error('Idle account detection job failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Detect accounts approaching INACTIVE status (175+ days)
     * Send warning notification if not yet warned
     */
    private function processApproachingInactive(): void
    {
        $days = config('status.idle.warning_days', 175); // 5 days before 180d threshold

        // Query using Spatie status relations
        $users = User::whereHas('statuses', function ($query) {
            $query->whereIn('name', [
                Status::ACTIVATED->value,
                Status::VERIFIED->value,
                Status::PROTECTED->value,
            ]);
        })
            ->where('last_activity_at', '<=', now()->subDays($days))
            ->get();

        foreach ($users as $user) {
            // TODO: Check if we already sent warning and send notification
            Log::info('Idle account warning candidate', [
                'user_id' => $user->id,
            ]);
        }
    }

    /**
     * Detect accounts that should transition to ARCHIVED (365+ days)
     */
    private function processBecomingArchived(): void
    {
        $users = User::whereHas('statuses', function ($query) {
            $query->whereIn('name', [
                Status::ACTIVATED->value,
                Status::VERIFIED->value,
                Status::INACTIVE->value,
            ]);
        })
            ->where('last_activity_at', '<=', now()->subDays(365))
            ->get();

        foreach ($users as $user) {
            $currentStatus = $user->getStatus();

            // Check if already archived
            if ($currentStatus === Status::ARCHIVED) {
                continue;
            }

            try {
                $this->transitionService->transition(
                    user: $user,
                    newStatus: Status::ARCHIVED,
                    reason: 'Automatic archival: No activity for 365+ days',
                    triggeredBy: null, // System-triggered
                );

                Log::info('Account auto-archived', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to auto-archive account', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Detect accounts eligible for GDPR purge (7+ years)
     * Anonymize personal data per GDPR right to be forgotten
     */
    private function processGdprPurge(): void
    {
        $years = config('status.gdpr.retention_years', 7);

        $users = User::whereHas('statuses', function ($query) {
            $query->where('name', Status::ARCHIVED->value);
        })
            ->where('last_activity_at', '<=', now()->subYears($years))
            ->get();

        foreach ($users as $user) {
            try {
                $this->anonymizeUserData($user);

                Log::info('Account anonymized for GDPR purge', [
                    'user_id' => $user->id,
                    'retention_years' => $years,
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to anonymize account for GDPR', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Anonymize user data per GDPR requirements
     */
    private function anonymizeUserData(User $user): void
    {
        $anonymizedId = 'ANON_' . hash('sha256', $user->id . config('app.key'));

        $user->update([
            'name' => $anonymizedId,
            'email' => $anonymizedId . '@anonymized.local',
            'phone' => null,
            'address' => null,
            'profile_picture' => null,
            'metadata' => [
                'anonymized_at' => now()->toIso8601String(),
                'anonymized_id' => $anonymizedId,
                'original_id' => $user->id,
            ],
        ]);

        $this->auditLogger->log(
            user: $user,
            event: 'gdpr_anonymization',
            metadata: [
                'anonymized_id' => $anonymizedId,
                'retention_completed' => true,
            ],
        );
    }
}
