<?php

declare(strict_types=1);

namespace Modules\Internship\Reports;

use Modules\Internship\Models\InternshipRegistration;
use Modules\Shared\Contracts\ExportableDataProvider;

class InternshipClassReportProvider implements ExportableDataProvider
{
    /**
     * {@inheritdoc}
     */
    public function getIdentifier(): string
    {
        return 'internship_class_summary';
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel(): string
    {
        return 'Internship Class Summary Report';
    }

    /**
     * {@inheritdoc}
     */
    public function getReportData(array $filters = []): array
    {
        $query = InternshipRegistration::with([
            'user',
            'placement.company',
            'internship',
            'mentor',
        ]);

        if (isset($filters['academic_year'])) {
            $query->where('academic_year', $filters['academic_year']);
        }

        $registrations = $query->get();

        return [
            'headers' => ['Student Name', 'Program', 'Placement', 'Mentor', 'Status'],
            'rows' => $registrations
                ->map(
                    fn($reg) => [
                        'Student Name' => $reg->user->name,
                        'Program' => $reg->internship->title,
                        'Placement' => $reg->placement?->company?->name ?? 'Not Assigned',
                        'Mentor' => $reg->mentor?->name ?? 'Not Assigned',
                        'Status' => $reg->getStatusLabel(),
                    ],
                )
                ->toArray(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplate(): string
    {
        return 'internship::reports.class-summary';
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
