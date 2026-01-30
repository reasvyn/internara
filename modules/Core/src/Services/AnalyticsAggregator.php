<?php

declare(strict_types=1);

namespace Modules\Core\Services;

use Modules\Assessment\Services\Contracts\AssessmentService;
use Modules\Core\Services\Contracts\AnalyticsAggregator as Contract;
use Modules\Internship\Services\Contracts\InternshipPlacementService;
use Modules\Internship\Services\Contracts\RegistrationService;
use Modules\Journal\Services\Contracts\JournalService;

class AnalyticsAggregator implements Contract
{
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
        $activeAcademicYear = setting('active_academic_year', '2025/2026');

        $totalInterns = $this->registrationService
            ->query([
                'academic_year' => $activeAcademicYear,
            ])
            ->count();

        $activePartners = $this->placementService->all()->count();

        return [
            'total_interns' => $totalInterns,
            'active_partners' => $activePartners,
            'placement_rate' => 100.0, // Mock for now
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getAtRiskStudents(int $limit = 5): array
    {
        // For simplicity, we look at the most recent active registrations
        $activeRegistrations = $this->registrationService
            ->query([
                'latest_status' => 'active',
            ])
            ->limit(20)
            ->get();

        $atRisk = [];

        foreach ($activeRegistrations as $registration) {
            $stats = $this->journalService->getEngagementStats([$registration->id]);
            $avgScore = $this->assessmentService->getAverageScore([$registration->id], 'mentor');

            $riskReasons = [];
            if ($stats['responsiveness'] < 50) {
                $riskReasons[] = 'Low journal verification';
            }
            if ($avgScore > 0 && $avgScore < 70) {
                $riskReasons[] = 'Low mentor assessment score';
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
}
