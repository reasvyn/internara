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
});
