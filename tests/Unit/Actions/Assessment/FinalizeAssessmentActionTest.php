<?php

declare(strict_types=1);

use App\Actions\Assessment\FinalizeAssessmentAction;
use App\Models\Assessment;
use App\Models\Competency;
use App\Models\Indicator;
use App\Models\Internship;
use App\Models\Registration;
use App\Models\Rubric;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('supervisor weight redistribution', function () {
    it('skips unscored supervisor competencies and redistributes weight', function () {
        $rubric = Rubric::factory()->create(['is_active' => true]);
        $internship = Internship::factory()->create();
        $registration = Registration::factory()->create([
            'internship_id' => $internship->id,
            'status' => 'active',
        ]);

        $teacherComp = Competency::factory()->create([
            'rubric_id' => $rubric->id,
            'name' => 'Technical Skills',
            'weight' => 70,
            'evaluator_role' => 'teacher',
        ]);
        $supervisorComp = Competency::factory()->create([
            'rubric_id' => $rubric->id,
            'name' => 'Industry Performance',
            'weight' => 30,
            'evaluator_role' => 'supervisor',
        ]);

        $indicator1 = Indicator::factory()->create([
            'competency_id' => $teacherComp->id,
            'max_score' => 100,
            'weight' => 100,
        ]);

        $assessment = Assessment::factory()->create([
            'registration_id' => $registration->id,
            'rubric_id' => $rubric->id,
            'type' => 'final',
            'content' => [
                'competencies' => [
                    $teacherComp->id => [
                        'indicators' => [
                            $indicator1->id => 80,
                        ],
                    ],
                ],
            ],
        ]);

        $teacher = User::factory()->create();
        $finalizer = app(FinalizeAssessmentAction::class)->execute($assessment, $teacher);

        expect($finalizer->score)->toBeGreaterThan(0);
    });

    it('throws when no competencies are scored', function () {
        $rubric = Rubric::factory()->create(['is_active' => true]);
        $competency = Competency::factory()->create([
            'rubric_id' => $rubric->id,
            'weight' => 100,
            'evaluator_role' => 'supervisor',
        ]);
        Indicator::factory()->create([
            'competency_id' => $competency->id,
            'max_score' => 100,
            'weight' => 100,
        ]);

        $registration = Registration::factory()->create(['status' => 'active']);
        $assessment = Assessment::factory()->create([
            'registration_id' => $registration->id,
            'rubric_id' => $rubric->id,
            'content' => ['competencies' => []],
        ]);

        $teacher = User::factory()->create();
        app(FinalizeAssessmentAction::class)->execute($assessment, $teacher);
    })->throws(RuntimeException::class, 'No competencies have been scored.');
});
