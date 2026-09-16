<?php

declare(strict_types=1);

use App\Modules\Assessment\Domain\Rubric\Models\Rubric;
use App\Modules\Assessment\Livewire\AssessmentGrading;
use App\Modules\Assessment\Livewire\AssessmentView;
use App\Modules\Assessment\Models\Assessment;
use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use App\Modules\Program\Domain\InternshipGroup\Models\InternshipGroup;
use App\Modules\Program\Domain\InternshipGroup\Models\InternshipGroupMember;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

function viewRubricStructure(): array
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

function viewMentorFor(Registration $registration, User $mentor, string $role = 'teacher'): void
{
    $group = InternshipGroup::factory()->create(['internship_id' => $registration->internship_id]);
    InternshipGroupMember::factory()->create([
        'internship_group_id' => $group->id,
        'registration_id' => $registration->id,
        'user_id' => $mentor->id,
        'role' => $role,
    ]);
}

describe('ARDA6 assessment views', function (): void {
    test('ARDA6-FR-ASM-010 FR-ASM-021 UC-ASM-005 NFR-ASM-006: student sees only finalized assessments for their own registration', function (): void {
        $student = User::factory()->create();
        $student->assignRole('student');
        $owned = Registration::factory()->create(['student_id' => $student->id]);
        $group = InternshipGroup::factory()->create(['internship_id' => $owned->internship_id]);
        InternshipGroupMember::factory()->create([
            'internship_group_id' => $group->id,
            'registration_id' => $owned->id,
            'user_id' => $student->id,
            'role' => 'student',
        ]);
        $other = Registration::factory()->create();
        Assessment::factory()->finalized()->create(['registration_id' => $owned->id, 'score' => 91]);
        Assessment::factory()->create(['registration_id' => $owned->id, 'score' => 30]);
        Assessment::factory()->finalized()->create(['registration_id' => $other->id, 'score' => 12]);

        $component = Livewire::actingAs($student)->test(AssessmentView::class);

        expect($component->instance()->assessments)->toHaveCount(1)
            ->and($component->instance()->assessments->first()->score)->toBe(91.0);
    });

    test('ARDA6-FR-ASM-010 FR-ASM-021 FR-ASM-022: grading separates teacher competencies from supervisor and system read-only competencies', function (): void {
        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');
        $registration = Registration::factory()->create();
        viewMentorFor($registration, $teacher, 'teacher');
        $rubric = Rubric::factory()->create(['internship_id' => $registration->internship_id, 'structure' => ['competencies' => [
            ['id' => 'teacher', 'name' => 'Teacher', 'weight' => 50, 'evaluator_role' => 'teacher', 'indicators' => []],
            ['id' => 'supervisor', 'name' => 'Supervisor', 'weight' => 30, 'evaluator_role' => 'supervisor', 'indicators' => []],
            ['id' => 'system', 'name' => 'System', 'weight' => 20, 'evaluator_role' => 'system', 'indicators' => []],
        ]]]);
        $assessment = Assessment::factory()->create(['registration_id' => $registration->id, 'rubric_id' => $rubric->id, 'scores_data' => ['competencies' => []]]);

        $component = Livewire::actingAs($teacher)->test(AssessmentGrading::class, ['registrationId' => $registration->id]);

        expect($component->instance()->evaluableCompetencies->pluck('id')->all())->toBe(['teacher'])
            ->and($component->instance()->readOnlyCompetencies->pluck('id')->all())->toBe(['supervisor', 'system'])
            ->and($component->instance()->assessmentId)->toBe($assessment->id);
    });

    test('ARDA6-FR-ASM-010 FR-ASM-018 NFR-ASM-003: finalized grading ignores score updates and auto import', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $registration = Registration::factory()->create();
        $rubric = Rubric::factory()->create([
            'internship_id' => $registration->internship_id,
            'structure' => viewRubricStructure(),
        ]);
        $assessment = Assessment::factory()->finalized()->create(['registration_id' => $registration->id, 'rubric_id' => $rubric->id, 'scores_data' => ['competencies' => []]]);

        $component = Livewire::actingAs($admin)->test(AssessmentGrading::class, ['registrationId' => $registration->id]);
        $component->call('updatedScores', 18, 'competency-one.indicator-one');
        $component->call('autoImport');

        expect($assessment->fresh()->scores_data)->toBe(['competencies' => []]);
    });

    test('ARDA6-FR-ASM-021 FR-ASM-022: anonymous users cannot access the student assessment route', function (): void {
        $this->get('/assessments')->assertRedirect();
    });
});
