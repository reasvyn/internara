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
        $query = InternshipPlacement::query()
            ->select(['id', 'company_id', 'internship_id'])
            ->with(['company:id,name', 'registrations:id,placement_id']);

        if (isset($filters['internship_id'])) {
            $query->where('internship_id', $filters['internship_id']);
        }

        $placements = $query->get();
        
        // 1. Collect all registration IDs for bulk processing
        $allRegistrationIds = $placements->flatMap->registrations->pluck('id')->toArray();
        
        // 2. Fetch required stats in single bulk calls
        $journalService = app(\Modules\Journal\Services\Contracts\JournalService::class);
        $assessmentService = app(\Modules\Assessment\Services\Contracts\AssessmentService::class);
        
        $allJournalStats = $journalService->getEngagementStats($allRegistrationIds);
        $allAvgScores = $assessmentService->getAverageScore($allRegistrationIds, 'mentor');

        $rows = $placements
            ->map(function ($placement) use ($allJournalStats, $allAvgScores) {
                $registrationIds = $placement->registrations->pluck('id')->toArray();
                
                // Aggregate stats for this specific placement from the bulk data
                $responsivenessSum = 0;
                $scoreSum = 0;
                $count = count($registrationIds);
                
                foreach ($registrationIds as $regId) {
                    $responsivenessSum += $allJournalStats[$regId]['responsiveness'] ?? 0;
                    $scoreSum += $allAvgScores[$regId] ?? 0;
                }

                return [
                    'Partner Name' => $placement->company?->name ?? 'Unknown',
                    'Total Interns' => $count,
                    'Responsiveness' => ($count > 0 ? round($responsivenessSum / $count, 2) : 0).'%',
                    'Avg Feedback' => number_format($count > 0 ? $scoreSum / $count : 0, 2).' / 100',
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
    public function getTemplate(): string
    {
        return 'internship::reports.partner-engagement';
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
