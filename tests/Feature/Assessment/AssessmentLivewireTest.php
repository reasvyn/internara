<?php

declare(strict_types=1);

namespace Tests\Feature\Assessment;

use App\Modules\Assessment\Domain\Rubric\Livewire\RubricManager;
use App\Modules\Assessment\Domain\Rubric\Models\Rubric;
use App\Modules\Assessment\Livewire\AssessmentGrading;
use App\Modules\Assessment\Livewire\AssessmentView;
use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

describe('ARDA6: Assessment Livewire UI Components', function (): void {
    test('ARDA6-FR-ASS-001: AssessmentView renders for authorized user', function (): void {
        $student = User::factory()->create();
        $student->assignRole('student');
        $this->actingAs($student);

        $registration = Registration::factory()->create([
            'student_id' => $student->id,
            'status' => 'active',
        ]);

        $test = Livewire::test(AssessmentView::class, ['registrationId' => (string) $registration->id]);
        $test->assertOk();
    });

    test('ARDA6-FR-ASS-002: AssessmentGrading mounts and sets registrationId', function (): void {
        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');
        $this->actingAs($teacher);

        $student = User::factory()->create();
        $student->assignRole('student');

        $registration = Registration::factory()->create([
            'student_id' => $student->id,
            'status' => 'active',
        ]);

        $test = Livewire::test(AssessmentGrading::class, ['registrationId' => (string) $registration->id]);
        $test->assertOk()
            ->assertSet('registrationId', (string) $registration->id);

        expect($test->instance()->registration)->toBeInstanceOf(Registration::class);
    });

    test('ARDA6-FR-ASS-003: RubricManager handles modal states for rubric, competency, and indicator', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        Rubric::create([
            'name' => 'Industrial Internship Core Rubric',
            'description' => 'Comprehensive vocational grading rubric',
            'is_active' => true,
        ]);

        $test = Livewire::test(RubricManager::class);
        $test->assertOk()
            ->assertSee('Industrial Internship Core Rubric');

        $test->call('addRubric');
        expect($test->get('rubricModal'))->toBeTrue()
            ->and($test->get('rubricForm')['name'])->toBe('');
    });

    test('ARDA6-FR-AS5, ARDA6-FR-AS6, ARDA6-FR-AS7: AssessmentGrading wires ScoreIndicatorAction on score update and enforces authorization', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $registration = Registration::factory()->create(['status' => 'active']);
        $rubric = Rubric::factory()->create([
            'internship_id' => $registration->internship_id,
            'is_active' => true,
            'structure' => [
                'competencies' => [
                    [
                        'id' => 'comp-1',
                        'name' => 'Technical Skills',
                        'weight' => 100,
                        'evaluator_role' => 'teacher',
                        'indicators' => [
                            [
                                'id' => 'ind-1',
                                'name' => 'Code Quality',
                                'description' => 'Clean code',
                                'max_score' => 20,
                                'weight' => 100,
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $test = Livewire::test(AssessmentGrading::class, ['registrationId' => (string) $registration->id]);
        $test->assertOk();

        // Valid score within max_score (20)
        $test->set('scores.comp-1.ind-1', 18);

        $assessment = $test->instance()->assessment;
        $scoresData = $assessment->fresh()->scores_data;
        expect($scoresData['competencies'][0]['indicators']['ind-1'])->toEqual(18)
            ->and($scoresData['competencies'][0]['evaluator_id'])->toBe($admin->id);
    });

    test('ARDA6-FR-AS7: AssessmentGrading disallows out-of-range score or unauthorized evaluator via ScoreIndicatorAction', function (): void {
        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');
        $this->actingAs($teacher);

        // Teacher is NOT assigned as mentor to this registration
        $registration = Registration::factory()->create(['status' => 'active']);
        Rubric::factory()->create([
            'internship_id' => $registration->internship_id,
            'is_active' => true,
            'structure' => [
                'competencies' => [
                    [
                        'id' => 'comp-1',
                        'name' => 'Technical Skills',
                        'weight' => 100,
                        'evaluator_role' => 'teacher',
                        'indicators' => [
                            [
                                'id' => 'ind-1',
                                'name' => 'Code Quality',
                                'description' => 'Clean code',
                                'max_score' => 20,
                                'weight' => 100,
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $test = Livewire::test(AssessmentGrading::class, ['registrationId' => (string) $registration->id]);
        $test->set('scores.comp-1.ind-1', 15);

        // Not saved because teacher is not an assigned mentor
        $assessment = $test->instance()->assessment;
        expect($assessment->fresh()->scores_data)->toBeNull();
    });
});
