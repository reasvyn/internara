<?php

declare(strict_types=1);

namespace Modules\Admin\Analytics\Services;

use Illuminate\Support\Facades\Cache;
use Modules\Admin\Analytics\Services\Contracts\AnalyticsAggregator as Contract;
use Modules\Admin\Services\Contracts\InfrastructureHealthService;
use Modules\Assessment\Services\Contracts\AssessmentService;
use Modules\Internship\Services\Contracts\InternshipPlacementService;
use Modules\Internship\Services\Contracts\RegistrationService;
use Modules\Journal\Services\Contracts\JournalService;
use Modules\Log\Models\Activity;
use Modules\Permission\Enums\Role;
use Modules\User\Models\User;

/**
 * Class AnalyticsAggregator
 *
 * Provides a unified logic layer for aggregating cross-domain analytics.
 */
class AnalyticsAggregator implements Contract
{
    /**
     * Create a new analytics aggregator instance.
     */
    public function __construct(
        protected RegistrationService $registrationService,
        protected InternshipPlacementService $placementService,
        protected JournalService $journalService,
        protected AssessmentService $assessmentService,
        protected InfrastructureHealthService $infraService,
    ) {}

    /**
     * {@inheritdoc}
     */
    public function getInstitutionalSummary(array $filters = []): array
    {
        $academicYear = $filters['academic_year'] ?? (string) setting('active_academic_year');
        $cacheKey = "institutional_summary_{$academicYear}";

        return Cache::remember($cacheKey, now()->addMinutes(15), function () use ($academicYear) {
            $totalInterns = $this->registrationService
                ->query(['academic_year' => $academicYear])
                ->count();

            $activePartners = $this->placementService->all(['id'])->count();

            return [
                'total_interns' => $totalInterns,
                'active_partners' => $activePartners,
                'placement_rate' => $this->calculatePlacementRate($totalInterns, $academicYear),
            ];
        });
    }

    /**
     * {@inheritdoc}
     */
    public function getAtRiskStudents(int $limit = 5, array $filters = []): array
    {
        $academicYear = $filters['academic_year'] ?? (string) setting('active_academic_year');

        $activeRegistrations = $this->registrationService
            ->query(['latest_status' => 'active', 'academic_year' => $academicYear])
            ->with('user:id,name')
            ->limit(20)
            ->get();

        if ($activeRegistrations->isEmpty()) {
            return [];
        }

        $registrationIds = $activeRegistrations->pluck('id')->toArray();
        $allEngagementStats = $this->journalService->getEngagementStats($registrationIds);
        $allAverageScores = $this->assessmentService->getAverageScore($registrationIds, 'mentor');

        $atRisk = [];

        foreach ($activeRegistrations as $registration) {
            $registrationId = (string) $registration->id;
            $stats = $allEngagementStats[$registrationId] ?? ['responsiveness' => 0];
            $avgScore = $allAverageScores[$registrationId] ?? 0;

            $riskReasons = [];

            if (($stats['responsiveness'] ?? 0) < 50) {
                $riskReasons[] = __('core::analytics.risks.low_verification');
            }

            if ($avgScore > 0 && $avgScore < 70) {
                $riskReasons[] = __('core::analytics.risks.low_score');
            }

            if (! empty($riskReasons)) {
                $atRisk[] = [
                    'id' => $registrationId,
                    'student_name' => $registration->user->name,
                    'reason' => implode(', ', $riskReasons),
                    'risk_level' => count($riskReasons) > 1 ? 'High' : 'Medium',
                ];
            }

            if (count($atRisk) >= $limit) {
                break;
            }
        }

        return $atRisk;
    }

    /**
     * {@inheritdoc}
     */
    public function getSecuritySummary(): array
    {
        return Cache::remember('security_summary', now()->addMinutes(5), function () {
            return [
                'failed_logins' => Activity::where('log_name', 'security')
                    ->where('event', 'failed_login_attempt')
                    ->where('created_at', '>=', now()->subDays(7))
                    ->count(),
                'throttled_attempts' => Activity::where('log_name', 'security')
                    ->where('event', 'like', '%throttled%')
                    ->where('created_at', '>=', now()->subDays(7))
                    ->count(),
                'suspicious_activities' => Activity::where('log_name', 'security')
                    ->latest()
                    ->limit(5)
                    ->get()
                    ->toArray(),
            ];
        });
    }

    /**
     * {@inheritdoc}
     */
    public function getRecentActivities(int $limit = 10): array
    {
        return Activity::with('causer:id,name,avatar_url')
            ->where('log_name', '!=', 'security')
            ->latest()
            ->limit($limit)
            ->get()
            ->map(function ($activity) {
                return [
                    'id' => $activity->id,
                    'description' => $activity->description,
                    'causer_name' => $activity->causer?->name ?? 'System',
                    'causer_avatar' => $activity->causer?->avatar_url,
                    'created_at' => $activity->created_at->diffForHumans(),
                    'properties' => $activity->properties,
                ];
            })
            ->toArray();
    }

    /**
     * {@inheritdoc}
     */
    public function getInfrastructureStatus(): array
    {
        $queue = $this->infraService->getQueueStatus();

        return [
            'queue_pending' => $queue['pending'],
            'queue_failed' => $queue['failed'],
            'db_size' => $this->infraService->getDatabaseSize(),
            'last_backup' => $this->infraService->getLastBackupTimestamp(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getUserDistribution(): array
    {
        return Cache::remember('user_distribution', now()->addMinutes(10), function () {
            $byRole = [];
            foreach (Role::cases() as $role) {
                $byRole[$role->value] = User::role($role->value)->count();
            }

            return [
                'by_role' => $byRole,
                'active_sessions' => $this->infraService->getActiveSessionCount(),
            ];
        });
    }

    /**
     * Calculates the institutional placement rate.
     */
    protected function calculatePlacementRate(int $totalInterns, string $academicYear): float
    {
        if ($totalInterns === 0) {
            return 0.0;
        }

        $placedInterns = $this->registrationService
            ->query(['academic_year' => $academicYear])
            ->whereNotNull('placement_id')
            ->count();

        return round(($placedInterns / $totalInterns) * 100, 2);
    }
}
