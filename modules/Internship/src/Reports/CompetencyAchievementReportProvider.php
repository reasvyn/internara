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
        $query = InternshipRegistration::with(['user', 'internship', 'placement']);

        if (isset($filters['academic_year'])) {
            $query->where('academic_year', $filters['academic_year']);
        }

        $registrations = $query->get();

        $rows = $registrations->map(function ($reg) {
            // Placeholder for competency achievement logic (to be expanded in v0.10.0)
            return [
                'Student Name' => $reg->user->name,
                'Placement' => $reg->placement?->company_name ?? '-',
                'Technical Skills' => 'N/A', // Placeholder
                'Soft Skills' => 'N/A',      // Placeholder
                'Total Progress' => '0%',    // Placeholder
            ];
        })->toArray();

        return [
            'headers' => ['Student Name', 'Placement', 'Technical Skills', 'Soft Skills', 'Total Progress'],
            'rows' => $rows,
        ];
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
