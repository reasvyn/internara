<?php

declare(strict_types=1);

namespace Modules\Admin\Analytics\Services;

use Modules\Admin\Analytics\Services\Contracts\AnalyticsAggregator as Contract;
use Modules\Assessment\Services\Contracts\AssessmentService;
use Modules\Internship\Services\Contracts\InternshipPlacementService;
use Modules\Internship\Services\Contracts\RegistrationService;
use Modules\Journal\Services\Contracts\JournalService;

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
     * Calculates the institutional placement rate.
     *
     * @param int $totalInterns The total number of registered interns.
     * @param string $academicYear The active academic year.
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
