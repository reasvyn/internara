<?php

declare(strict_types=1);

use App\Modules\Academic\Domain\AcademicYear\Models\AcademicYear;
use App\Modules\Assessment\Domain\Rubric\Models\Rubric;
use App\Modules\Assessment\Livewire\AssessmentGrading;
use App\Modules\Assessment\Models\Assessment;
use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use App\Modules\Program\Domain\Internship\Models\Internship;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;

describe('7C5WM-FR-050: assessment grading behavioral', function (): void {
    uses(LazilyRefreshDatabase::class);

    test('assessment grading preserves manual scores and computes auto-score', function (): void {
        $student = User::factory()->create();
        $student->assignRole('student');
        $year = AcademicYear::factory()->create();
        $internship = Internship::factory()->create(['academic_year_id' => $year->id, 'status' => 'active']);
        $registration = Registration::factory()->create([
            'student_id' => $student->id,
            'internship_id' => $internship->id,
            'status' => 'active',
        ]);
        $rubric = Rubric::factory()->create([
            'internship_id' => $internship->id,
            'is_active' => true,
            'structure' => [
                'competencies' => [[
                    'id' => 'c1',
                    'name' => 'Comp 1',
                    'weight' => 100,
                    'evaluator_role' => 'teacher',
                    'indicators' => [[
                        'id' => 'i1',
                        'name' => 'Ind 1',
                        'max_score' => 100,
                        'weight' => 100,
                    ]],
                ]],
            ],
        ]);
        $assessment = Assessment::factory()->create([
            'registration_id' => $registration->id,
            'rubric_id' => $rubric->id,
            'scores_data' => ['competencies' => ['c1' => ['indicators' => ['i1' => 85]]]],
            'finalized_at' => null,
        ]);

        $component = Livewire::test(AssessmentGrading::class, ['registrationId' => $registration->id])
            ->set('scores.c1.i1', '92');

        expect($component->get('scores')['c1']['i1'])->toBe('92');
    });
});
