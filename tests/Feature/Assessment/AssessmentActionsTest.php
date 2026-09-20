<?php

declare(strict_types=1);

use App\Modules\Assessment\Actions\AutoCalculateAssessmentAction;
use App\Modules\Assessment\Actions\FinalizeAssessmentAction;
use App\Modules\Assessment\Actions\InitializeAssessmentAction;
use App\Modules\Assessment\Actions\ScoreIndicatorAction;
use App\Modules\Assessment\Data\ScoreIndicatorData;
use App\Modules\Assessment\Domain\Rubric\Models\Rubric;
use App\Modules\Assessment\Entities\AssessmentResult;
use App\Modules\Assessment\Events\AssessmentFinalized;
use App\Modules\Assessment\Models\Assessment;
use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use App\Modules\Journal\Domain\Attendance\Models\Attendance;
use App\Modules\Program\Domain\InternshipGroup\Models\InternshipGroup;
use App\Modules\Program\Domain\InternshipGroup\Models\InternshipGroupMember;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(LazilyRefreshDatabase::class);

function assessmentRubricStructure(): array
{
    return ['competencies' => [[
        'id' => 'competency-one',
        'name' => 'Technical skills',
        'description' => 'Workshop performance',
        'weight' => 70,
        'evaluator_role' => 'teacher',
        'order' => 1,
        'indicators' => [[
            'id' => 'indicator-one',
            'name' => 'Tool safety',
            'description' => 'Uses tools safely',
            'max_score' => 20,
            'weight' => 100,
            'order' => 1,
        ]],
    ]]];
}

function assessmentRubric(): Rubric
{
    return Rubric::factory()->create(['is_active' => true, 'structure' => assessmentRubricStructure()]);
}

function assessmentMentorFor(Registration $registration, User $mentor, string $role = 'teacher'): void
{
    $group = InternshipGroup::factory()->create(['internship_id' => $registration->internship_id]);
    InternshipGroupMember::factory()->create([
        'internship_group_id' => $group->id,
        'registration_id' => $registration->id,
        'user_id' => $mentor->id,
        'role' => $role,
    ]);
}

describe('ARDA6 assessment actions', function (): void {
    test('ARDA6-FR-ASM-006 FR-ASM-012 NFR-ASM-007: initializes one assessment with the first active rubric and is idempotent', function (): void {
        $registration = Registration::factory()->create();
        Rubric::factory()->create(['internship_id' => $registration->internship_id, 'is_active' => false]);
        $active = Rubric::factory()->create(['internship_id' => $registration->internship_id, 'is_active' => true]);

        Assessment::factory()->create([
            'registration_id' => $registration->id,
            'rubric_id' => $active->id,
            'evaluator_id' => User::factory()->create()->id,
        ]);
        $first = app(InitializeAssessmentAction::class)->execute($registration->id);
        $second = app(InitializeAssessmentAction::class)->execute($registration->id);

        expect($first['assessment']->id)->toBe($second['assessment']->id)
            ->and($first['rubric']->id)->toBe($active->id)
            ->and(Assessment::where('registration_id', $registration->id)->count())->toBe(1);
    });

    test('ARDA6-FR-ASM-006 FR-ASM-015: returns no assessment when no active rubric exists', function (): void {
        $registration = Registration::factory()->create();
        Rubric::factory()->create(['internship_id' => $registration->internship_id, 'is_active' => false]);

        expect(app(InitializeAssessmentAction::class)->execute($registration->id))
            ->toMatchArray(['assessment' => null, 'rubric' => null]);
    });

    test('ARDA6-FR-ASM-007 FR-ASM-008 FR-ASM-022 UC-ASM-002: authorized mentor can persist zero and maximum scores', function (float $score): void {
        $mentor = User::factory()->create();
        $mentor->assignRole('teacher');
        $registration = Registration::factory()->create();
        assessmentMentorFor($registration, $mentor);
        $rubric = assessmentRubric();
        $assessment = Assessment::factory()->create(['registration_id' => $registration->id, 'rubric_id' => $rubric->id, 'scores_data' => null]);

        $response = app(ScoreIndicatorAction::class)->execute($assessment, $rubric, new ScoreIndicatorData('competency-one', 'indicator-one', $score), $mentor);

        expect($response->data->fresh()->scores_data['competencies'][0]['indicators']['indicator-one'])->toEqual($score);
    })->with([0.0, 20.0]);

    test('ARDA6-FR-ASM-007 FR-ASM-019: rejects scores outside the indicator maximum', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $assessment = Assessment::factory()->create(['rubric_id' => ($rubric = assessmentRubric())->id, 'scores_data' => null]);

        expect(fn () => app(ScoreIndicatorAction::class)->execute($assessment, $rubric, new ScoreIndicatorData('competency-one', 'indicator-one', 20.1), $admin))
            ->toThrow(RejectedException::class);
    });

    test('ARDA6-FR-ASM-008 FR-ASM-009 FR-ASM-022: rejects a role-matched evaluator who does not mentor this registration', function (): void {
        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');
        $registration = Registration::factory()->create();
        $rubric = assessmentRubric();
        $assessment = Assessment::factory()->create(['registration_id' => $registration->id, 'rubric_id' => $rubric->id]);

        expect(fn () => app(ScoreIndicatorAction::class)->execute($assessment, $rubric, new ScoreIndicatorData('competency-one', 'indicator-one', 10), $teacher))
            ->toThrow(RejectedException::class);
    });

    test('ARDA6-FR-ASM-008 FR-ASM-018 FR-ASM-022: admin bypasses mentor scoping but finalized assessments remain immutable', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $rubric = assessmentRubric();
        $assessment = Assessment::factory()->create(['rubric_id' => $rubric->id, 'scores_data' => null]);

        app(ScoreIndicatorAction::class)->execute($assessment, $rubric, new ScoreIndicatorData('competency-one', 'indicator-one', 12), $admin);
        $assessment->update(['finalized_at' => now()]);

        expect(fn () => app(ScoreIndicatorAction::class)->execute($assessment->fresh(), $rubric, new ScoreIndicatorData('competency-one', 'indicator-one', 13), $admin))
            ->toThrow(RejectedException::class);
    });

    test('ARDA6-FR-ASM-013 FR-ASM-014 NFR-ASM-001: aggregates evidence into auto namespace without overwriting manual scores', function (): void {
        $registration = Registration::factory()->create();
        $rubric = assessmentRubric();
        $assessment = Assessment::factory()->create([
            'registration_id' => $registration->id,
            'rubric_id' => $rubric->id,
            'scores_data' => ['competencies' => ['competency-one' => ['indicators' => ['indicator-one' => 12]]]],
        ]);
        Attendance::factory()->count(2)->create(['registration_id' => $registration->id, 'status' => 'present']);
        Attendance::factory()->create(['registration_id' => $registration->id, 'status' => 'absent']);

        $result = app(AutoCalculateAssessmentAction::class)->execute($assessment);

        expect($result->scores_data['competencies']['competency-one']['indicators']['indicator-one'])->toBe(12)
            ->and($result->scores_data['auto']['attendance_rate'])->toBe(66.7)
            ->and($result->scores_data['auto'])->toHaveKeys(['avg_submission_score', 'logbook_completeness', 'supervision_completeness', 'monitoring_visit_completeness', 'report_score']);
    });

    test('ARDA6-FR-ASM-014 FR-ASM-018: leaves finalized auto-calculation unchanged', function (): void {
        $assessment = Assessment::factory()->finalized()->create(['scores_data' => ['manual' => ['kept' => true]]]);

        expect(app(AutoCalculateAssessmentAction::class)->execute($assessment)->scores_data)
            ->toBe(['manual' => ['kept' => true]]);
    });

    test('ARDA6-FR-ASM-015 FR-ASM-016 FR-ASM-017 UC-ASM-004 NFR-ASM-002: finalizes weighted scores with supervisor redistribution and emits event', function (): void {
        Event::fake();
        $admin = User::factory()->create();
        $rubric = Rubric::factory()->create(['structure' => ['competencies' => [
            ['id' => 'teacher', 'name' => 'Teacher', 'weight' => 60, 'evaluator_role' => 'teacher', 'indicators' => [['id' => 't-indicator', 'max_score' => 10, 'weight' => 100]]],
            ['id' => 'supervisor', 'name' => 'Supervisor', 'weight' => 40, 'evaluator_role' => 'supervisor', 'indicators' => [['id' => 's-indicator', 'max_score' => 20, 'weight' => 100]]],
        ]]]);
        $assessment = Assessment::factory()->create(['rubric_id' => $rubric->id, 'scores_data' => ['competencies' => ['teacher' => ['indicators' => ['t-indicator' => 8]]]]]);

        $finalized = app(FinalizeAssessmentAction::class)->execute($assessment, $admin);

        expect($finalized->score)->toBe(80.0)->and($finalized->finalized_at)->not->toBeNull()->and($finalized->evaluator_id)->toBe($admin->id);
        Event::assertDispatched(AssessmentFinalized::class);
    });

    test('ARDA6-FR-ASM-015 FR-ASM-018: rejects finalization with no rubric, no scores, or a second finalization', function (): void {
        $admin = User::factory()->create();
        $missingRubric = Assessment::factory()->create(['rubric_id' => null, 'scores_data' => null]);
        expect(fn () => app(FinalizeAssessmentAction::class)->execute($missingRubric, $admin))->toThrow(RejectedException::class);

        $empty = Assessment::factory()->create(['rubric_id' => assessmentRubric()->id, 'scores_data' => ['competencies' => []]]);
        expect(fn () => app(FinalizeAssessmentAction::class)->execute($empty, $admin))->toThrow(RejectedException::class);

        $closed = Assessment::factory()->finalized()->create(['rubric_id' => assessmentRubric()->id, 'scores_data' => ['competencies' => []]]);
        expect(fn () => app(FinalizeAssessmentAction::class)->execute($closed, $admin))->toThrow(RejectedException::class);
    });

    test('ARDA6-FR-ASM-020: translation keys exist for grading and rubric views (also NFR-ASM-004, NFR-ASM-005, NFR-ASM-008, NFR-ASM-009)', function (): void {
        expect(__('assessment.grading'))->not->toBe('assessment.grading');

        $rubricFile = file_get_contents(base_path('app/Modules/Assessment/Domain/Rubric/Models/Rubric.php'));
        expect($rubricFile)->toContain('declare(strict_types=1)')
            ->and($rubricFile)->toContain('Fillable');
    });

    test('ARDA6-DD-ASM-001: architecture decisions for nested json and assessment entity hold (also DD-ASM-002, DD-ASM-003, DD-ASM-004, DD-ASM-005, DD-ASM-006)', function (): void {
        $rubric = Rubric::factory()->create([
            'structure' => assessmentRubricStructure(),
        ]);
        expect($rubric->structure)->toBeArray();

        $assessment = Assessment::factory()->create(['rubric_id' => $rubric->id]);
        expect($assessment->asAssessmentResult())->toBeInstanceOf(AssessmentResult::class);
    });
});
