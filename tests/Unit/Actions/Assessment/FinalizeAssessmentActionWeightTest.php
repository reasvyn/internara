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

it('redistributes weight when supervisor does not score', function () {
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

    $indicator = Indicator::factory()->create([
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
                        $indicator->id => 50,
                    ],
                ],
            ],
        ],
    ]);

    $teacher = User::factory()->create();
    $finalized = app(FinalizeAssessmentAction::class)->execute($assessment, $teacher);

    $expectedScore = round((50 / 100) * 100, 1);
    expect($finalized->score)->toBe($expectedScore);
});
