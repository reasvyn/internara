<?php

declare(strict_types=1);

namespace Modules\Admin\Analytics\Services;

use Illuminate\Support\Facades\DB;
use Modules\Admin\Analytics\Services\Contracts\AnalyticsAggregator as Contract;
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
 * This service orchestrates data from multiple functional modules to generate
 * institutional insights and risk assessments.
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
    ) {}

    /**
     * {@inheritdoc}
     */
    public function getInstitutionalSummary(): array
    {
        $activeAcademicYear = setting('active_academic_year');
        $cacheKey = "institutional_summary_{$activeAcademicYear}";

        return \Illuminate\Support\Facades\Cache::remember(
            $cacheKey,
            now()->addMinutes(15),
            function () use ($activeAcademicYear) {
                $totalInterns = $this->registrationService
                    ->query(['academic_year' => $activeAcademicYear])
                    ->count();

                $activePartners = $this->placementService->all(['id'])->count();

                return [
                    'total_interns' => $totalInterns,
                    'active_partners' => $activePartners,
                    'placement_rate' => $this->calculatePlacementRate($totalInterns, (string) $activeAcademicYear),
                ];
            },
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getAtRiskStudents(int $limit = 5): array
    {
        // 1. Retrieve the most recent active registrations
        $activeRegistrations = $this->registrationService
            ->query(['latest_status' => 'active'])
            ->with('user:id,name') // Select only required columns
            ->limit(20)
            ->get();

        if ($activeRegistrations->isEmpty()) {
            return [];
        }

        $registrationIds = $activeRegistrations->pluck('id')->toArray();

        // 2. Fetch required stats in bulk (Eliminating N+1)
        $allEngagementStats = $this->journalService->getEngagementStats($registrationIds);
        $allAverageScores = $this->assessmentService->getAverageScore($registrationIds, 'mentor');

        $atRisk = [];

        foreach ($activeRegistrations as $registration) {
            $registrationId = (string) $registration->id;

            // Get stats from pre-fetched maps
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
        return \Illuminate\Support\Facades\Cache::remember('security_summary', now()->addMinutes(5), function () {
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
    public function getInfrastructureStatus(): array
    {
        return [
            'queue_pending' => DB::table('jobs')->count(),
            'queue_failed' => DB::table('failed_jobs')->count(),
            'db_size' => $this->getDatabaseSize(),
            'last_backup' => setting('last_successful_backup_at'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getUserDistribution(): array
    {
        return \Illuminate\Support\Facades\Cache::remember('user_distribution', now()->addMinutes(10), function () {
            $byRole = [];
            foreach (Role::cases() as $role) {
                $byRole[$role->value] = User::role($role->value)->count();
            }

            return [
                'by_role' => $byRole,
                'active_sessions' => DB::table('sessions')
                    ->where('last_activity', '>=', now()->subMinutes(15)->getTimestamp())
                    ->count(),
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

    /**
     * Get the current database size in a human-readable format.
     */
    protected function getDatabaseSize(): string
    {
        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");

        try {
            if ($driver === 'sqlite') {
                $path = config("database.connections.{$connection}.database");
                if (file_exists($path)) {
                    $size = filesize($path);
                    return $this->formatBytes($size);
                }
            }

            if ($driver === 'mysql') {
                $dbName = config("database.connections.{$connection}.database");
                $res = DB::select("SELECT SUM(data_length + index_length) AS size FROM information_schema.TABLES WHERE table_schema = ?", [$dbName]);
                return $this->formatBytes((int) ($res[0]->size ?? 0));
            }
        } catch (\Exception $e) {
            return 'Unknown';
        }

        return 'N/A';
    }

    /**
     * Format bytes to human readable format.
     */
    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
