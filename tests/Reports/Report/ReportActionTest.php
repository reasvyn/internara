<?php

declare(strict_types=1);

use App\Enrollment\Registration\Models\Registration;
use App\Reports\Report\Actions\CalculateFinalGradeAction;
use App\Reports\Report\Actions\FinalizeReportAction;
use App\Reports\Report\Enums\ReportStatus;
use App\Reports\Report\Models\Report;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('R6BMW-FR-AR2: finalizing a report captures the archival snapshot via ReportObserver', function () {
    $admin = User::factory()->create();
    $this->actingAs($admin);

    $registration = Registration::factory()->create();
    $report = Report::factory()->create([
        'registration_id' => $registration->id,
        'archived_data' => null,
    ]);

    $finalized = app(FinalizeReportAction::class)->execute($report, $admin->id);

    expect($finalized->status)->toBe(ReportStatus::FINALIZED)
        ->and($finalized->finalized_by)->toBe($admin->id)
        ->and($finalized->finalized_at)->not->toBeNull()
        ->and($finalized->archived_data)->not->toBeEmpty()
        ->and($finalized->archived_data['student_name'])->toBe($registration->student->name);
});

test('R6BMW-FR-GC4: a legitimate zero score is stored as 0.0, not erased to null', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $report = Report::factory()->create([
        'supervisor_score' => 0,
        'teacher_score' => null,
        'exam_score' => null,
        'final_score' => null,
        'grade_letter' => null,
    ]);

    $calculated = app(CalculateFinalGradeAction::class)->execute($report);

    expect($calculated->supervisor_score)->toBe(0.0)
        ->and($calculated->supervisor_score)->not->toBeNull()
        ->and($calculated->teacher_score)->toBe(0.0)
        ->and($calculated->exam_score)->toBe(0.0)
        ->and($calculated->final_score)->toBe(0.0)
        ->and($calculated->grade_letter)->toBe('E');
});
