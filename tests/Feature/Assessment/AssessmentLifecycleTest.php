<?php

declare(strict_types=1);

use App\Modules\Assessment\Actions\AutoCalculateAssessmentAction;
use App\Modules\Assessment\Actions\FinalizeAssessmentAction;
use App\Modules\Assessment\Actions\ScoreIndicatorAction;
use App\Modules\Assessment\Data\ScoreIndicatorData;
use App\Modules\Assessment\Domain\Rubric\Models\Rubric;
use App\Modules\Assessment\Events\AssessmentFinalized;
use App\Modules\Assessment\Models\Assessment;
use App\Modules\Assignment\Domain\Submission\Models\Submission;
use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use App\Modules\Journal\Domain\Attendance\Models\Attendance;
use App\Modules\Journal\Domain\Logbook\Models\Logbook;
use App\Modules\Journal\Domain\MonitoringVisit\Models\MonitoringVisit;
use App\Modules\Journal\Domain\SupervisionLog\Models\SupervisionLog;
use App\Modules\Program\Domain\InternshipGroup\Models\InternshipGroup;
use App\Modules\Program\Domain\InternshipGroup\Models\InternshipGroupMember;
use App\Modules\Report\Domain\StudentReport\Models\StudentReport;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(LazilyRefreshDatabase::class);

function lifecycleRubric(?array $competencies = null): Rubric
{
    return Rubric::factory()->create(['structure' => ['competencies' => $competencies ?? [[
        'id' => 'c-1', 'name' => 'Technical', 'weight' => 100, 'evaluator_role' => 'teacher', 'indicators' => [
            ['id' => 'i-1', 'name' => 'Safety', 'max_score' => 20, 'weight' => 100],
        ],
    ]]]]);
}

function lifecycleAdmin(): User
{
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    return $admin;
}

describe('ARDA6 assessment lifecycle', function (): void {
    test('ARDA6-FR-ASM-007 FR-ASM-019: rejects an unknown competency or indicator before persistence', function (): void {
        $admin = lifecycleAdmin();
        $rubric = lifecycleRubric();
        $assessment = Assessment::factory()->create(['rubric_id' => $rubric->id, 'scores_data' => null]);

        expect(fn () => app(ScoreIndicatorAction::class)->execute($assessment, $rubric, new ScoreIndicatorData('missing', 'i-1', 5), $admin))
            ->toThrow(RejectedException::class)
            ->and(fn () => app(ScoreIndicatorAction::class)->execute($assessment, $rubric, new ScoreIndicatorData('c-1', 'missing', 5), $admin))
            ->toThrow(RejectedException::class);
        expect($assessment->fresh()->scores_data)->toBeNull();
    });

    test('ARDA6-FR-ASM-007: accepts a score at both boundaries and records evaluator metadata', function (float $score): void {
        $admin = lifecycleAdmin();
        $rubric = lifecycleRubric();
        $assessment = Assessment::factory()->create(['rubric_id' => $rubric->id, 'scores_data' => null]);

        app(ScoreIndicatorAction::class)->execute($assessment, $rubric, new ScoreIndicatorData('c-1', 'i-1', $score), $admin);
        $stored = $assessment->fresh()->scores_data['competencies'][0];

        expect($stored['id'])->toBe('c-1')
            ->and($stored['indicators']['i-1'])->toEqual($score)
            ->and($stored['evaluator_id'])->toBe($admin->id)
            ->and($stored['evaluated_at'])->toBeString();
    })->with([0.0, 20.0]);

    test('ARDA6-FR-ASM-007: rejects negative and over-maximum scores without changing an existing score', function (): void {
        $admin = lifecycleAdmin();
        $rubric = lifecycleRubric();
        $assessment = Assessment::factory()->create(['rubric_id' => $rubric->id, 'scores_data' => ['competencies' => [['id' => 'c-1', 'indicators' => ['i-1' => 8]]]]]);

        foreach ([-0.01, 20.01] as $score) {
            expect(fn () => app(ScoreIndicatorAction::class)->execute($assessment, $rubric, new ScoreIndicatorData('c-1', 'i-1', $score), $admin))
                ->toThrow(RejectedException::class);
        }

        expect($assessment->fresh()->scores_data['competencies'][0]['indicators']['i-1'])->toBe(8);
    });

    test('ARDA6-FR-ASM-008 FR-ASM-022: rejects an evaluator with the wrong role', function (): void {
        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');
        $rubric = lifecycleRubric([['id' => 'c-1', 'weight' => 100, 'evaluator_role' => 'supervisor', 'indicators' => [['id' => 'i-1', 'max_score' => 10, 'weight' => 100]]]]);
        $assessment = Assessment::factory()->create(['rubric_id' => $rubric->id]);

        expect(fn () => app(ScoreIndicatorAction::class)->execute($assessment, $rubric, new ScoreIndicatorData('c-1', 'i-1', 5), $teacher))
            ->toThrow(RejectedException::class, __('assessment.not_authorized'));
    });

    test('ARDA6-FR-ASM-008 FR-ASM-009: permits a matching mentor and records proxy-eligible supervisor scoring', function (): void {
        $supervisor = User::factory()->create();
        $supervisor->assignRole('supervisor');
        $registration = Registration::factory()->create();
        $group = InternshipGroup::factory()->create(['internship_id' => $registration->internship_id]);
        InternshipGroupMember::factory()->create(['internship_group_id' => $group->id, 'registration_id' => $registration->id, 'user_id' => $supervisor->id, 'role' => 'supervisor']);
        $rubric = lifecycleRubric([['id' => 'c-1', 'weight' => 100, 'evaluator_role' => 'supervisor', 'indicators' => [['id' => 'i-1', 'max_score' => 10, 'weight' => 100]]]]);
        $assessment = Assessment::factory()->create(['registration_id' => $registration->id, 'rubric_id' => $rubric->id, 'scores_data' => null]);

        $result = app(ScoreIndicatorAction::class)->execute($assessment, $rubric, new ScoreIndicatorData('c-1', 'i-1', 7), $supervisor);

        expect($result->data->scores_data['competencies'][0]['indicators']['i-1'])->toEqual(7);
    });

    test('ARDA6-FR-ASM-012: permits each assessment type for separate evaluator records', function (): void {
        $registration = Registration::factory()->create();
        $rubric = lifecycleRubric();
        $evaluator = User::factory()->create();

        foreach (['midterm', 'final', 'periodic', 'industry'] as $type) {
            Assessment::factory()->create(['registration_id' => $registration->id, 'rubric_id' => $rubric->id, 'evaluator_id' => $evaluator->id, 'assessment_type' => $type]);
        }

        expect(Assessment::where('registration_id', $registration->id)->count())->toBe(4);
    });

    test('ARDA6-FR-ASM-012: allows teacher and supervisor final records for the same registration', function (): void {
        $registration = Registration::factory()->create();
        $rubric = lifecycleRubric();
        $teacher = User::factory()->create();
        $supervisor = User::factory()->create();

        Assessment::factory()->create(['registration_id' => $registration->id, 'rubric_id' => $rubric->id, 'evaluator_id' => $teacher->id, 'assessment_type' => 'final']);
        Assessment::factory()->create(['registration_id' => $registration->id, 'rubric_id' => $rubric->id, 'evaluator_id' => $supervisor->id, 'assessment_type' => 'final']);

        expect(Assessment::where('registration_id', $registration->id)->where('assessment_type', 'final')->count())->toBe(2);
    });

    test('ARDA6-FR-ASM-013 FR-ASM-014: calculates all evidence streams into auto data while preserving manual data', function (): void {
        $registration = Registration::factory()->create();
        $assessment = Assessment::factory()->create(['registration_id' => $registration->id, 'scores_data' => ['competencies' => [['id' => 'c-1', 'indicators' => ['i-1' => 11]]]]]);
        Submission::factory()->verified()->create(['registration_id' => $registration->id, 'score' => 80]);
        Submission::factory()->verified()->create(['registration_id' => $registration->id, 'score' => 100]);
        foreach (['submitted', 'verified', 'draft'] as $index => $status) {
            Logbook::factory()->create(['registration_id' => $registration->id, 'date' => now()->subDays($index + 1)->toDateString(), 'status' => $status]);
        }
        Attendance::factory()->create(['registration_id' => $registration->id, 'status' => 'present']);
        Attendance::factory()->create(['registration_id' => $registration->id, 'status' => 'late']);
        Attendance::factory()->create(['registration_id' => $registration->id, 'status' => 'absent']);
        SupervisionLog::factory()->create(['registration_id' => $registration->id, 'status' => 'reviewed']);
        SupervisionLog::factory()->create(['registration_id' => $registration->id, 'status' => 'draft']);
        MonitoringVisit::factory()->create(['registration_id' => $registration->id, 'is_verified' => true]);
        MonitoringVisit::factory()->create(['registration_id' => $registration->id, 'is_verified' => false]);
        StudentReport::factory()->create(['registration_id' => $registration->id, 'status' => 'finalized', 'final_score' => 88]);

        $auto = app(AutoCalculateAssessmentAction::class)->execute($assessment)->scores_data;

        expect($auto['competencies'][0]['indicators']['i-1'])->toBe(11)
            ->and($auto['auto'])->toMatchArray(['avg_submission_score' => 90.0, 'logbook_completeness' => 66.7, 'attendance_rate' => 66.7, 'supervision_completeness' => 50.0, 'monitoring_visit_completeness' => 50.0])
            ->and($auto['auto']['report_score'])->toBeNull();
    });

    test('ARDA6-FR-ASM-014 NFR-ASM-003: finalized assessments refuse auto calculation and score mutation', function (): void {
        $admin = lifecycleAdmin();
        $rubric = lifecycleRubric();
        $assessment = Assessment::factory()->finalized()->create(['rubric_id' => $rubric->id, 'scores_data' => ['manual' => ['keep' => true]]]);

        expect(app(AutoCalculateAssessmentAction::class)->execute($assessment)->scores_data)->toBe(['manual' => ['keep' => true]])
            ->and(fn () => app(ScoreIndicatorAction::class)->execute($assessment, $rubric, new ScoreIndicatorData('c-1', 'i-1', 4), $admin))
            ->toThrow(RejectedException::class);
    });

    test('ARDA6-FR-ASM-016 NFR-ASM-002: finalization normalizes weighted indicators deterministically', function (): void {
        $admin = lifecycleAdmin();
        $rubric = lifecycleRubric([['id' => 'c-1', 'weight' => 60, 'evaluator_role' => 'teacher', 'indicators' => [['id' => 'i-1', 'max_score' => 20, 'weight' => 25], ['id' => 'i-2', 'max_score' => 10, 'weight' => 75]]], ['id' => 'c-2', 'weight' => 40, 'evaluator_role' => 'teacher', 'indicators' => [['id' => 'i-3', 'max_score' => 100, 'weight' => 100]]]]);
        $scores = ['competencies' => ['c-1' => ['indicators' => ['i-1' => 10, 'i-2' => 5]], 'c-2' => ['indicators' => ['i-3' => 80]]]];
        $assessment = Assessment::factory()->create(['rubric_id' => $rubric->id, 'scores_data' => $scores]);

        $finalized = app(FinalizeAssessmentAction::class)->execute($assessment, $admin);

        expect($finalized->score)->toBe(62.0);
    });

    test('ARDA6-FR-ASM-017 NFR-ASM-001: finalization stores score, timestamp, evaluator, and dispatches event', function (): void {
        Event::fake();
        $admin = lifecycleAdmin();
        $rubric = lifecycleRubric();
        $assessment = Assessment::factory()->create(['rubric_id' => $rubric->id, 'scores_data' => ['competencies' => ['c-1' => ['indicators' => ['i-1' => 15]]]]]);

        $result = app(FinalizeAssessmentAction::class)->execute($assessment, $admin);
        $stored = $assessment->fresh();

        expect($result->id)->toBe($stored->id)->and($stored->score)->toBe(75.0)->and($stored->finalized_at)->not->toBeNull()->and($stored->evaluator_id)->toBe($admin->id);
        Event::assertDispatched(AssessmentFinalized::class, fn (AssessmentFinalized $event): bool => $event->assessment->id === $assessment->id);
    });

    test('ARDA6-FR-ASM-015 FR-ASM-018: rejects a second finalization and preserves the frozen score', function (): void {
        $admin = lifecycleAdmin();
        $assessment = Assessment::factory()->finalized()->create(['score' => 73]);

        expect(fn () => app(FinalizeAssessmentAction::class)->execute($assessment, $admin))
            ->toThrow(RejectedException::class, __('assessment.already_finalized'))
            ->and($assessment->fresh()->score)->toBe(73.0);
    });

    test('ARDA6-FR-ASM-021: assessment result bridge exposes the immutable finalized state', function (): void {
        $assessment = Assessment::factory()->finalized()->create(['score' => 91, 'scores_data' => ['competencies' => [['indicators' => ['i-1' => 91]]]]]);

        $result = $assessment->asAssessmentResult();

        expect($result->isFinalized())->toBeTrue()->and($result->calculateTotalScore())->toBe(91.0);
    });
});
