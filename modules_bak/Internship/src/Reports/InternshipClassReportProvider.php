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
        $query = InternshipRegistration::query()
            ->select([
                'id',
                'student_id',
                'internship_id',
                'placement_id',
                'mentor_id',
                'status',
                'academic_year',
            ])
            ->with([
                'student:id,name',
                'internship:id,title',
                'placement:id,company_id',
                'placement.company:id,name',
                'mentor:id,name',
            ]);

        if (isset($filters['academic_year'])) {
            $query->where('academic_year', $filters['academic_year']);
        }

        $rows = [];

        // Using cursor to iterate through records without loading all into memory at once
        // although we return an array, this helps during the hydration phase.
        foreach ($query->cursor() as $reg) {
            $rows[] = [
                'Student Name' => $reg->student->name,
                'Program' => $reg->internship->title,
                'Placement' => $reg->placement?->company?->name ?? 'Not Assigned',
                'Mentor' => $reg->mentor?->name ?? 'Not Assigned',
                'Status' => $reg->getStatusLabel(),
            ];
        }

        return [
            'headers' => ['Student Name', 'Program', 'Placement', 'Mentor', 'Status'],
            'rows' => $rows,
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
