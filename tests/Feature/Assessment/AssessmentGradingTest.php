<?php

declare(strict_types=1);

use App\Actions\Assessment\AutoCalculateAssessmentAction;
use App\Actions\Assessment\FinalizeAssessmentAction;
use App\Actions\Assessment\ScoreIndicatorAction;
use App\Models\Assessment;
use App\Models\Competency;
use App\Models\Indicator;
use App\Models\Internship;
use App\Models\Mentor;
use App\Models\Registration;
use App\Models\Rubric;
use App\Models\Submission;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach (['super_admin', 'admin', 'teacher', 'student', 'supervisor'] as $role) {
        Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
    }

    $this->admin = User::factory()->create()->assignRole('admin');
    $this->teacher = User::factory()->create()->assignRole('teacher');
    $this->supervisor = User::factory()->create()->assignRole('supervisor');
    $this->student = User::factory()->create()->assignRole('student');

    $this->internship = Internship::factory()->create([
        'status' => 'active',
        'start_date' => now()->subMonth(),
        'end_date' => now()->addMonths(5),
    ]);

    $this->registration = Registration::factory()->create([
        'internship_id' => $this->internship->id,
        'status' => 'active',
    ]);

    // Mark registration as active via spatie/laravel-model-status
    $this->registration->setStatus('active', 'test');
});

// ─── Rubric Setup ───────────────────────────────────────────────────

function createDefaultRubric(Internship $internship): Rubric
{
    $rubric = Rubric::factory()->create([
        'internship_id' => $internship->id,
        'is_active' => true,
    ]);

    $teacherCompetency = Competency::factory()->create([
        'rubric_id' => $rubric->id,
        'name' => 'Teacher Assessment',
        'weight' => 60,
        'evaluator_role' => 'teacher',
        'order' => 1,
    ]);

    Indicator::factory()->create([
        'competency_id' => $teacherCompetency->id,
        'name' => 'Report Quality',
        'max_score' => 100,
        'weight' => 50,
        'order' => 1,
    ]);

    Indicator::factory()->create([
        'competency_id' => $teacherCompetency->id,
        'name' => 'Attitude',
        'max_score' => 100,
        'weight' => 50,
        'order' => 2,
    ]);

    $supervisorCompetency = Competency::factory()->create([
        'rubric_id' => $rubric->id,
        'name' => 'Supervisor Assessment',
        'weight' => 40,
        'evaluator_role' => 'supervisor',
        'order' => 2,
    ]);

    Indicator::factory()->create([
        'competency_id' => $supervisorCompetency->id,
        'name' => 'Technical Skills',
        'max_score' => 100,
        'weight' => 100,
        'order' => 1,
    ]);

    return $rubric;
}

function assignMentor(User $user, Registration $registration, string $type): void
{
    $mentor = Mentor::factory()->create([
        'user_id' => $user->id,
        'type' => $type,
    ]);
    $registration->mentors()->attach($mentor->id, ['role' => $type]);
}

// ─── ScoreIndicatorAction Tests ─────────────────────────────────────

it('can score an indicator', function () {
    $rubric = createDefaultRubric($this->internship);
    assignMentor($this->teacher, $this->registration, Mentor::TYPE_SCHOOL_TEACHER);

    $assessment = Assessment::factory()->create([
        'registration_id' => $this->registration->id,
        'rubric_id' => $rubric->id,
        'type' => 'final',
    ]);

    $indicator = $rubric->competencies->first()->indicators->first();
    $action = app(ScoreIndicatorAction::class);

    $result = $action->execute($assessment, $indicator->id, 85, $this->teacher);

    $content = $result->content;
    expect($content['competencies'][$indicator->competency_id]['indicators'][$indicator->id])->toEqual(85);
});

it('throws exception when scoring finalized assessment', function () {
    $rubric = createDefaultRubric($this->internship);
    $assessment = Assessment::factory()->create([
        'registration_id' => $this->registration->id,
        'rubric_id' => $rubric->id,
        'finalized_at' => now(),
    ]);

    $indicator = $rubric->competencies->first()->indicators->first();
    $action = app(ScoreIndicatorAction::class);

    expect(fn () => $action->execute($assessment, $indicator->id, 85, $this->teacher))
        ->toThrow(RuntimeException::class, 'Cannot modify a finalized assessment');
});

it('throws exception when score exceeds max', function () {
    $rubric = createDefaultRubric($this->internship);
    assignMentor($this->teacher, $this->registration, Mentor::TYPE_SCHOOL_TEACHER);

    $assessment = Assessment::factory()->create([
        'registration_id' => $this->registration->id,
        'rubric_id' => $rubric->id,
        'type' => 'final',
    ]);

    $indicator = $rubric->competencies->first()->indicators->first();
    $action = app(ScoreIndicatorAction::class);

    expect(fn () => $action->execute($assessment, $indicator->id, 999, $this->teacher))
        ->toThrow(RuntimeException::class, 'must be between 0 and 100');
});

it('throws exception when user has role but is not assigned mentor', function () {
    $rubric = createDefaultRubric($this->internship);
    assignMentor($this->teacher, $this->registration, Mentor::TYPE_INDUSTRY_SUPERVISOR);

    $assessment = Assessment::factory()->create([
        'registration_id' => $this->registration->id,
        'rubric_id' => $rubric->id,
        'type' => 'final',
    ]);

    $indicator = $rubric->competencies->first()->indicators->first();
    $action = app(ScoreIndicatorAction::class);

    expect(fn () => $action->execute($assessment, $indicator->id, 85, $this->teacher))
        ->toThrow(RuntimeException::class, 'not assigned as a mentor');
});

it('throws exception when user lacks role entirely', function () {
    $rubric = createDefaultRubric($this->internship);
    $assessment = Assessment::factory()->create([
        'registration_id' => $this->registration->id,
        'rubric_id' => $rubric->id,
        'type' => 'final',
    ]);

    // student tries to score
    $indicator = $rubric->competencies->first()->indicators->first();
    $action = app(ScoreIndicatorAction::class);

    expect(fn () => $action->execute($assessment, $indicator->id, 85, $this->student))
        ->toThrow(RuntimeException::class, 'not authorized');
});

// ─── AutoCalculateAssessmentAction Tests ────────────────────────────

it('auto-imports submission and logbook scores', function () {
    $rubric = createDefaultRubric($this->internship);
    $assessment = Assessment::factory()->create([
        'registration_id' => $this->registration->id,
        'rubric_id' => $rubric->id,
        'content' => [],
    ]);

    Submission::factory()->count(3)->create([
        'registration_id' => $this->registration->id,
        'status' => 'verified',
        'score' => 80,
    ]);

    $action = app(AutoCalculateAssessmentAction::class);
    $result = $action->execute($assessment);

    $content = $result->content;
    expect($content['auto']['avg_submission_score'])->toEqual(80);
    expect($content['auto']['logbook_completeness'])->toBe(0);
});

// ─── FinalizeAssessmentAction Tests ─────────────────────────────────

it('finalizes assessment with correct weighted score', function () {
    $rubric = createDefaultRubric($this->internship);
    assignMentor($this->teacher, $this->registration, Mentor::TYPE_SCHOOL_TEACHER);
    assignMentor($this->supervisor, $this->registration, Mentor::TYPE_INDUSTRY_SUPERVISOR);

    $assessment = Assessment::factory()->create([
        'registration_id' => $this->registration->id,
        'rubric_id' => $rubric->id,
        'content' => [],
    ]);

    // Score teacher competency (60% weight): both indicators at 80
    $teacherComp = $rubric->competencies()->where('evaluator_role', 'teacher')->first();
    foreach ($teacherComp->indicators as $indicator) {
        $assessment = app(ScoreIndicatorAction::class)->execute($assessment, $indicator->id, 80, $this->teacher);
    }

    // Score supervisor competency (40% weight): indicator at 90
    $supervisorComp = $rubric->competencies()->where('evaluator_role', 'supervisor')->first();
    foreach ($supervisorComp->indicators as $indicator) {
        $assessment = app(ScoreIndicatorAction::class)->execute($assessment->fresh(), $indicator->id, 90, $this->supervisor);
    }

    $action = app(FinalizeAssessmentAction::class);
    $result = $action->execute($assessment->fresh(), $this->admin);

    // Teacher: avg 80 * 60% = 48
    // Supervisor: 90 * 40% = 36
    // Total: 84
    expect($result->score)->toBe(84.0);
    expect($result->finalized_at)->not->toBeNull();
});

it('throws exception when finalizing already finalized assessment', function () {
    $rubric = createDefaultRubric($this->internship);
    $assessment = Assessment::factory()->create([
        'registration_id' => $this->registration->id,
        'rubric_id' => $rubric->id,
        'finalized_at' => now(),
    ]);

    $action = app(FinalizeAssessmentAction::class);

    expect(fn () => $action->execute($assessment, $this->admin))
        ->toThrow(RuntimeException::class, 'already finalized');
});

it('throws exception when rubric is missing', function () {
    $assessment = Assessment::factory()->create([
        'registration_id' => $this->registration->id,
        'rubric_id' => null,
    ]);

    $action = app(FinalizeAssessmentAction::class);

    expect(fn () => $action->execute($assessment, $this->admin))
        ->toThrow(RuntimeException::class, 'must have a rubric');
});
