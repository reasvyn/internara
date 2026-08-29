<?php

declare(strict_types=1);

namespace App\Modules\Reports\Domain\Report\Actions;

use App\Modules\Core\Actions\BaseProcessAction;
use App\Modules\Reports\Domain\Report\Models\Report;

final class CaptureReportSnapshotAction extends BaseProcessAction
{
    public function execute(Report $report): void
    {
        if (! $report->registration_id || ! $report->registration) {
            return;
        }

        $this->step('capture_snapshot', function () use ($report) {
            $registration = $report->registration;
            $student = $registration->student;
            $profile = $student?->profile;
            $internship = $registration->internship;
            $placement = $registration->placement;
            $company = $placement?->company;
            $department = $profile?->department;

            $mentors = $registration->mentors;

            $archivedData = array_merge($report->archived_data ?? [], array_filter([
                'captured_at' => now()->toIso8601String(),
                'student_name' => $student?->name,
                'student_email' => $student?->email,
                'student_number' => $profile?->id_number,
                'student_phone' => $profile?->phone,
                'internship_name' => $internship?->name,
                'company_name' => $company?->name
                    ?? ($registration->proposed_company_details['company_name'] ?? null),
                'company_address' => $company?->address
                    ?? ($registration->proposed_company_details['address'] ?? null),
                'department_name' => $department?->name,
                'supervisor_name' => $mentors->first(fn ($m) => $m->hasRole('supervisor'))?->name,
                'teacher_name' => $mentors->first(fn ($m) => $m->hasRole('teacher'))?->name,
                'academic_year' => $internship?->academicYear?->name,
            ], fn ($v) => $v !== null));

            $report->archived_data = $archivedData;
            $report->saveQuietly();
        });

        $this->logProgress('report_snapshot_captured', [
            'report_id' => $report->id,
        ]);
    }

    protected function moduleName(): string
    {
        return 'Reports';
    }
}
