<?php

declare(strict_types=1);

use App\Modules\Assignment\Domain\Submission\Models\Submission;
use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use App\Modules\Report\Domain\StudentReport\Actions\CalculateFinalGradeAction;
use App\Modules\Report\Domain\StudentReport\Actions\CreateStudentReportAction;
use App\Modules\Report\Domain\StudentReport\Actions\FinalizeStudentReportAction;
use App\Modules\Report\Domain\StudentReport\Data\CreateStudentReportData;
use App\Modules\Report\Domain\StudentReport\Enums\StudentReportStatus;
use App\Modules\Report\Domain\StudentReport\Events\GradeCalculated;
use App\Modules\Report\Domain\StudentReport\Events\StudentReportFinalized;
use App\Modules\Report\Domain\StudentReport\Models\StudentReport;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(LazilyRefreshDatabase::class);

describe('R6BMW student report actions', function (): void {
    test('R6BMW-FR-RPT-001/002/003: creates one empty draft for the registration', function (): void {
        $registration = Registration::factory()->create();

        $report = app(CreateStudentReportAction::class)->execute(
            new CreateStudentReportData($registration->id),
        );

        expect($report->registration_id)->toBe($registration->id)
            ->and($report->status)->toBe(StudentReportStatus::DRAFT)
            ->and($report->supervisor_score)->toBeNull()
            ->and($report->teacher_score)->toBeNull()
            ->and($report->exam_score)->toBeNull()
            ->and($report->final_score)->toBeNull()
            ->and($report->grade_letter)->toBeNull();
    });

    test('R6BMW-FR-RPT-004/007/008/009: calculates weighted scores and national letter bands', function (): void {
        Event::fake([GradeCalculated::class]);
        $registration = Registration::factory()->create();
        $registration->internship->update(['grading_weights' => [
            'supervisor' => 50,
            'teacher' => 20,
            'assignment' => 20,
            'exam' => 10,
        ]]);
        $report = StudentReport::factory()->for($registration)->create([
            'supervisor_score' => 80,
            'teacher_score' => 70,
            'exam_score' => 90,
        ]);
        Submission::factory()->for($registration)->graded(100)->create();
        Submission::factory()->for($registration)->graded(80)->create();

        $calculated = app(CalculateFinalGradeAction::class)->execute($report);

        expect($calculated->final_score)->toBe(81.0)
            ->and($calculated->grade_letter)->toBe('B')
            ->and($calculated->supervisor_score)->toBe(80.0)
            ->and($calculated->teacher_score)->toBe(70.0)
            ->and($calculated->exam_score)->toBe(90.0);
        Event::assertDispatched(GradeCalculated::class, fn (GradeCalculated $event): bool => $event->report->is($calculated));
    });

    test('R6BMW-FR-RPT-012/017: finalizes a calculated draft and emits its event', function (): void {
        Event::fake([StudentReportFinalized::class]);
        $report = StudentReport::factory()->create([
            'final_score' => 91,
            'grade_letter' => 'A',
            'archived_data' => ['existing' => 'value'],
        ]);
        $actor = User::factory()->create();

        $finalized = app(FinalizeStudentReportAction::class)->execute($report, $actor->id);

        expect($finalized->status)->toBe(StudentReportStatus::FINALIZED)
            ->and($finalized->finalized_by)->toBe($actor->id)
            ->and($finalized->finalized_at)->not->toBeNull();
        Event::assertDispatched(StudentReportFinalized::class, fn (StudentReportFinalized $event): bool => $event->studentReport->is($finalized));
    });

    test('R6BMW-FR-RPT-013: rejects a second finalization of a terminal report', function (): void {
        $report = StudentReport::factory()->finalized()->create();

        expect(fn () => app(FinalizeStudentReportAction::class)->execute($report, User::factory()->create()->id))
            ->toThrow(RejectedException::class);
    });
});
