<?php

declare(strict_types=1);

namespace Modules\Core\Analytics\Services;

use Modules\Assessment\Services\Contracts\AssessmentService;
use Modules\Core\Analytics\Services\Contracts\AnalyticsAggregator as Contract;
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

        $totalInterns = $this->registrationService
            ->query(['academic_year' => $activeAcademicYear])
            ->count();

        $activePartners = $this->placementService->all()->count();

        return [
            'total_interns' => $totalInterns,
            'active_partners' => $activePartners,
            'placement_rate' => $this->calculatePlacementRate($totalInterns),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getAtRiskStudents(int $limit = 5): array
    {
        // Retrieve the most recent active registrations for analysis
        $activeRegistrations = $this->registrationService
            ->query(['latest_status' => 'active'])
            ->limit(20)
            ->get();

        $atRisk = [];

        foreach ($activeRegistrations as $registration) {
            $stats = $this->journalService->getEngagementStats([$registration->id]);
            $avgScore = $this->assessmentService->getAverageScore([$registration->id], 'mentor');

            $riskReasons = [];

            if ($stats['responsiveness'] < 50) {
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
     */
    protected function calculatePlacementRate(int $totalInterns): float
    {
        if ($totalInterns === 0) {
            return 0.0;
        }

        // TODO: Implement actual placement count logic via RegistrationService query
        return 100.0;
    }
}
