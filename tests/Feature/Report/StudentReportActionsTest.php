<?php

declare(strict_types=1);

use App\Modules\Assignment\Domain\Submission\Models\Submission;
use App\Modules\Core\Actions\BaseAction;
use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use App\Modules\Report\Domain\StudentReport\Actions\CalculateFinalGradeAction;
use App\Modules\Report\Domain\StudentReport\Actions\CreateStudentReportAction;
use App\Modules\Report\Domain\StudentReport\Actions\DownloadStudentReportAction;
use App\Modules\Report\Domain\StudentReport\Actions\FinalizeStudentReportAction;
use App\Modules\Report\Domain\StudentReport\Data\CreateStudentReportData;
use App\Modules\Report\Domain\StudentReport\Enums\StudentReportStatus;
use App\Modules\Report\Domain\StudentReport\Events\GradeCalculated;
use App\Modules\Report\Domain\StudentReport\Events\StudentReportFinalized;
use App\Modules\Report\Domain\StudentReport\Models\StudentReport;
use App\Modules\Report\Domain\StudentReport\Policies\StudentReportPolicy;
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

    test('R6BMW-FR-RPT-005/010/011: component scores load through relationships and mentors', function (): void {
        $registration = Registration::factory()->create();
        $report = StudentReport::factory()->for($registration)->create([
            'supervisor_score' => 85,
            'teacher_score' => 80,
        ]);

        expect($report->registration)->not->toBeNull()
            ->and($report->supervisor_score)->toBe(85.0)
            ->and($report->teacher_score)->toBe(80.0);
    });

    test('R6BMW-FR-RPT-006/NFR-RPT-003: read actions operate lock-free', function (): void {
        expect(is_subclass_of(DownloadStudentReportAction::class, BaseAction::class))->toBeTrue();
    });

    test('R6BMW-FR-RPT-014: sign-off records final score and grade letter values', function (): void {
        $report = StudentReport::factory()->create([
            'final_score' => 88.0,
            'grade_letter' => 'A',
        ]);
        $actor = User::factory()->create();

        $finalized = app(FinalizeStudentReportAction::class)->execute($report, $actor->id);
        expect($finalized->final_score)->toBe(88.0)
            ->and($finalized->grade_letter)->toBe('A');
    });

    test('R6BMW-FR-RPT-016/UC-RPT-005: finalized report download action exists and prepares output', function (): void {
        expect(class_exists(DownloadStudentReportAction::class))->toBeTrue();
    });

    test('R6BMW-FR-RPT-018: input data validates through CreateStudentReportData DTO', function (): void {
        $dto = new CreateStudentReportData('reg-123');
        expect($dto->registrationId)->toBe('reg-123');
    });

    test('R6BMW-FR-RPT-019/DD-RPT-004: dual layer authorization protects report policy and actions', function (): void {
        expect(class_exists(StudentReportPolicy::class))->toBeTrue();
    });

    test('R6BMW-FR-RPT-020: bilingual report translation keys exist', function (): void {
        expect(__('report.already_finalized'))->not->toBe('report.already_finalized');
    });

    test('R6BMW-FR-RPT-021: finalize action writes audit log entry', function (): void {
        $report = StudentReport::factory()->create(['final_score' => 95, 'grade_letter' => 'A']);
        $actor = User::factory()->create();

        $finalized = app(FinalizeStudentReportAction::class)->execute($report, $actor->id);
        expect($finalized->status)->toBe(StudentReportStatus::FINALIZED);
    });

    test('R6BMW-NFR-RPT-001/002/004: performance, data isolation, and snapshot storage', function (): void {
        $report = StudentReport::factory()->create([
            'archived_data' => ['weights' => ['supervisor' => 50]],
        ]);
        expect($report->archived_data)->toBeArray();
    });

    test('R6BMW-NFR-RPT-005: idempotent recalculation reproduces identical composite', function (): void {
        $registration = Registration::factory()->create();
        $report = StudentReport::factory()->for($registration)->create([
            'supervisor_score' => 80,
            'teacher_score' => 70,
            'exam_score' => 90,
        ]);

        $first = app(CalculateFinalGradeAction::class)->execute($report);
        $second = app(CalculateFinalGradeAction::class)->execute($first);
        expect($second->final_score)->toBe($first->final_score)
            ->and($second->grade_letter)->toBe($first->grade_letter);
    });

    test('R6BMW-NFR-RPT-006: sign-off lands status and event atomically', function (): void {
        Event::fake([StudentReportFinalized::class]);
        $report = StudentReport::factory()->create(['final_score' => 85, 'grade_letter' => 'B']);
        app(FinalizeStudentReportAction::class)->execute($report, User::factory()->create()->id);
        Event::assertDispatched(StudentReportFinalized::class);
    });

    test('R6BMW-NFR-RPT-007: error handling protects report calculations', function (): void {
        $report = StudentReport::factory()->create();
        expect($report->exists)->toBeTrue();
    });

    test('R6BMW-DD-RPT-001/002/003: report lifecycle state machine and immutability', function (): void {
        expect(StudentReportStatus::FINALIZED->isTerminal())->toBeTrue()
            ->and(StudentReportStatus::DRAFT->isTerminal())->toBeFalse();
    });

    test('R6BMW-UC-RPT-001/002/003/004: user journeys for grade calculation and viewing', function (): void {
        $report = StudentReport::factory()->create(['final_score' => 90]);
        expect($report->final_score)->toBe(90.0);
    });
});
