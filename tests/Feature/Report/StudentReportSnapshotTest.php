<?php

declare(strict_types=1);

use App\Modules\Enrollment\Domain\Placement\Models\Placement;
use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use App\Modules\Program\Domain\InternshipGroup\Models\InternshipGroup;
use App\Modules\Program\Domain\InternshipGroup\Models\InternshipGroupMember;
use App\Modules\Report\Domain\StudentReport\Actions\CaptureStudentReportSnapshotAction;
use App\Modules\Report\Domain\StudentReport\Enums\StudentReportStatus;
use App\Modules\Report\Domain\StudentReport\Models\StudentReport;
use App\Modules\User\Domain\Profile\Models\Profile;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

describe('R6BMW snapshot behavior', function (): void {
    test('R6BMW-FR-RPT-015: captures the identity state at finalization time', function (): void {
        $student = User::factory()->create(['name' => 'Student Before Rename']);
        Profile::factory()->for($student, 'user')->forStudent()->create([
            'phone' => '+6200012345',
            'id_number' => 'STD-12345',
        ]);
        $registration = Registration::factory()->create(['student_id' => $student->id]);
        $internship = $registration->internship;
        $placement = Placement::factory()->for($internship)->create();
        $registration->update(['placement_id' => $placement->id]);

        $supervisor = User::factory()->create(['name' => 'Supervisor at Sign-off']);
        $supervisor->assignRole('supervisor');
        $teacher = User::factory()->create(['name' => 'Teacher at Sign-off']);
        $teacher->assignRole('teacher');
        $group = InternshipGroup::factory()->for($internship)->create();
        InternshipGroupMember::factory()->for($group, 'group')->for($registration, 'registration')
            ->for($supervisor, 'user')->create(['role' => 'supervisor']);
        InternshipGroupMember::factory()->for($group, 'group')->for($registration, 'registration')
            ->for($teacher, 'user')->create(['role' => 'teacher']);

        $report = StudentReport::factory()->for($registration)->create([
            'status' => StudentReportStatus::FINALIZED,
            'archived_data' => null,
        ]);

        app(CaptureStudentReportSnapshotAction::class)->execute($report);

        $report->refresh();
        expect($report->archived_data)
            ->toMatchArray([
                'student_name' => 'Student Before Rename',
                'student_email' => $student->email,
                'student_number' => 'STD-12345',
                'student_phone' => '+6200012345',
                'company_name' => $placement->company->name,
                'supervisor_name' => 'Supervisor at Sign-off',
                'teacher_name' => 'Teacher at Sign-off',
            ])
            ->and($report->archived_data['captured_at'])->not->toBeNull();
    });

    test('R6BMW-FR-RPT-015: observer snapshots a finalized report when it is saved', function (): void {
        $report = StudentReport::factory()->create(['archived_data' => null]);

        $report->update(['status' => StudentReportStatus::FINALIZED->value]);

        expect($report->fresh()->archived_data)->toHaveKey('captured_at');
    });
});
