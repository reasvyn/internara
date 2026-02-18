<?php

declare(strict_types=1);

namespace Modules\Internship\Reports;

use Modules\Internship\Models\InternshipRegistration;
use Modules\Shared\Contracts\ExportableDataProvider;

/**
 * Provides competency achievement summary for an internship class.
 */
class CompetencyAchievementReportProvider implements ExportableDataProvider
{
    /**
     * {@inheritdoc}
     */
    public function getIdentifier(): string
    {
        return 'competency_achievement_summary';
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel(): string
    {
        return 'Competency Achievement Summary';
    }

    /**
     * {@inheritdoc}
     */
    public function getReportData(array $filters = []): array
    {
        $query = InternshipRegistration::query()
            ->select(['id', 'student_id', 'internship_id', 'placement_id', 'status', 'academic_year'])
            ->with(['student:id,name', 'internship:id,title', 'placement:id,company_id', 'placement.company:id,name']);

        if (isset($filters['academic_year'])) {
            $query->where('academic_year', $filters['academic_year']);
        }

        $rows = [];
        
        foreach ($query->cursor() as $reg) {
            // Placeholder for competency achievement logic (to be expanded in v0.10.0)
            $rows[] = [
                'Student Name' => $reg->student->name,
                'Placement' => $reg->placement?->company?->name ?? '-',
                'Technical Skills' => 'N/A', // Placeholder
                'Soft Skills' => 'N/A', // Placeholder
                'Total Progress' => '0%', // Placeholder
            ];
        }

        return [
            'headers' => [
                'Student Name',
                'Placement',
                'Technical Skills',
                'Soft Skills',
                'Total Progress',
            ],
            'rows' => $rows,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplate(): string
    {
        return 'internship::reports.competency-achievement';
    }

    /**
     * {@inheritdoc}
     */
    public function getFilterRules(): array
    {
        return [
            'academic_year' => 'required|string',
        ];
    }
}
