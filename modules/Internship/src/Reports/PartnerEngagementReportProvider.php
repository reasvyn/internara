<?php

declare(strict_types=1);

namespace Modules\Internship\Reports;

use Modules\Internship\Models\InternshipPlacement;
use Modules\Shared\Contracts\ExportableDataProvider;

/**
 * Provides engagement analytics per Industry Partner (Placement).
 */
class PartnerEngagementReportProvider implements ExportableDataProvider
{
    /**
     * {@inheritdoc}
     */
    public function getIdentifier(): string
    {
        return 'partner_engagement_analytics';
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel(): string
    {
        return 'Partner Engagement Analytics';
    }

    /**
     * {@inheritdoc}
     */
    public function getReportData(array $filters = []): array
    {
        $query = InternshipPlacement::with(['registrations']);

        if (isset($filters['internship_id'])) {
            $query->where('internship_id', $filters['internship_id']);
        }

        $placements = $query->get();

        $rows = $placements
            ->map(function ($placement) {
                $registrationIds = $placement->registrations->pluck('id')->toArray();

                $journalStats = app(
                    \Modules\Journal\Services\Contracts\JournalService::class,
                )->getEngagementStats($registrationIds);

                $avgScore = app(
                    \Modules\Assessment\Services\Contracts\AssessmentService::class,
                )->getAverageScore($registrationIds, 'mentor');

                return [
                    'Partner Name' => $placement->company_name,
                    'Total Interns' => count($registrationIds),
                    'Responsiveness' => $journalStats['responsiveness'].'%',
                    'Avg Feedback' => number_format($avgScore, 2).' / 100',
                ];
            })
            ->toArray();

        return [
            'headers' => ['Partner Name', 'Total Interns', 'Responsiveness', 'Avg Feedback'],
            'rows' => $rows,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFilterRules(): array
    {
        return [
            'internship_id' => 'nullable|uuid',
        ];
    }
}
