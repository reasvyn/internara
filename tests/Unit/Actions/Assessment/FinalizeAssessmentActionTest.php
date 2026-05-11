<?php

declare(strict_types=1);

use App\Actions\Assessment\FinalizeAssessmentAction;
use Database\Factories\AssessmentFactory;
use Database\Factories\CompetencyFactory;
use Database\Factories\IndicatorFactory;
use Database\Factories\RubricFactory;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('execute', function () {
    it('throws if assessment is already finalized', function () {
        $assessment = AssessmentFactory::new()->finalized()->create();
        $user = UserFactory::new()->create();

        expect(fn () => app(FinalizeAssessmentAction::class)->execute($assessment, $user))
            ->toThrow(RuntimeException::class, 'already finalized');
    });

    it('throws if assessment has no rubric', function () {
        $assessment = AssessmentFactory::new()->create(['rubric_id' => null]);
        $user = UserFactory::new()->create();

        expect(fn () => app(FinalizeAssessmentAction::class)->execute($assessment, $user))
            ->toThrow(RuntimeException::class, 'must have a rubric');
    });

    it('throws if no competencies have been scored', function () {
        $rubric = RubricFactory::new()->create();
        $assessment = AssessmentFactory::new()->create(['rubric_id' => $rubric->id]);
        $user = UserFactory::new()->create();

        expect(fn () => app(FinalizeAssessmentAction::class)->execute($assessment, $user))
            ->toThrow(RuntimeException::class, 'No competencies have been scored');
    });

    it('finalizes assessment with weighted competency scores', function () {
        $rubric = RubricFactory::new()->create();
        $competency = CompetencyFactory::new()->create([
            'rubric_id' => $rubric->id,
            'weight' => 100,
        ]);
        $indicator = IndicatorFactory::new()->create([
            'competency_id' => $competency->id,
            'max_score' => 100,
            'weight' => 100,
        ]);

        $assessment = AssessmentFactory::new()->create([
            'rubric_id' => $rubric->id,
            'content' => [
                'competencies' => [
                    $competency->id => [
                        'indicators' => [
                            $indicator->id => 80,
                        ],
                    ],
                ],
            ],
        ]);
        $user = UserFactory::new()->create();

        $result = app(FinalizeAssessmentAction::class)->execute($assessment, $user);

        expect($result->finalized_at)->not->toBeNull()
            ->and($result->evaluator_id)->toBe($user->id)
            ->and($result->score)->toBe(80.0);
    });

    it('calculates weighted score across multiple competencies', function () {
        $rubric = RubricFactory::new()->create();

        $comp1 = CompetencyFactory::new()->create([
            'rubric_id' => $rubric->id,
            'weight' => 60,
        ]);
        $ind1 = IndicatorFactory::new()->create([
            'competency_id' => $comp1->id,
            'max_score' => 100,
            'weight' => 100,
        ]);

        $comp2 = CompetencyFactory::new()->create([
            'rubric_id' => $rubric->id,
            'weight' => 40,
        ]);
        $ind2 = IndicatorFactory::new()->create([
            'competency_id' => $comp2->id,
            'max_score' => 100,
            'weight' => 100,
        ]);

        $assessment = AssessmentFactory::new()->create([
            'rubric_id' => $rubric->id,
            'content' => [
                'competencies' => [
                    $comp1->id => [
                        'indicators' => [$ind1->id => 100],
                    ],
                    $comp2->id => [
                        'indicators' => [$ind2->id => 50],
                    ],
                ],
            ],
        ]);
        $user = UserFactory::new()->create();

        $result = app(FinalizeAssessmentAction::class)->execute($assessment, $user);

        expect($result->score)->toBe(80.0);
    });
});
