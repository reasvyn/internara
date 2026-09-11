<?php

declare(strict_types=1);

use App\Modules\Assessment\Data\ScoreIndicatorData;
use App\Modules\Assessment\Data\UpdateAssessmentScoresData;

describe('ARDA6: ScoreIndicatorData DTO', function (): void {
    test('ARDA6-FR-ASM-007: fromArray maps the competency, indicator, and score', function (): void {
        $dto = ScoreIndicatorData::fromArray(['competencyId' => 'c-1', 'indicatorId' => 'i-1', 'score' => 85.5]);

        expect($dto->competencyId)->toBe('c-1');
        expect($dto->indicatorId)->toBe('i-1');
        expect($dto->score)->toBe(85.5);
    });

    test('ARDA6-FR-ASM-007: fromArray accepts snake_case keys', function (): void {
        $dto = ScoreIndicatorData::fromArray(['competency_id' => 'c-2', 'indicator_id' => 'i-2', 'score' => 70]);

        expect($dto->competencyId)->toBe('c-2');
        expect($dto->indicatorId)->toBe('i-2');
        expect($dto->score)->toBe(70.0);
    });

    test('ARDA6-FR-ASM-007: fromArray throws when the score is missing', function (): void {
        expect(fn (): ScoreIndicatorData => ScoreIndicatorData::fromArray(['competencyId' => 'c', 'indicatorId' => 'i']))
            ->toThrow(InvalidArgumentException::class, 'score');
    });

    test('ARDA6-FR-ASM-007: from accepts arrays and arrayables, rejects scalars', function (): void {
        $payload = ['competencyId' => 'c', 'indicatorId' => 'i', 'score' => 90];

        expect(ScoreIndicatorData::from($payload)->score)->toBe(90.0);

        $source = new class
        {
            public function toArray(): array
            {
                return ['competencyId' => 'c', 'indicatorId' => 'i', 'score' => 55];
            }
        };

        expect(ScoreIndicatorData::from($source)->score)->toBe(55.0);
        expect(fn (): ScoreIndicatorData => ScoreIndicatorData::from('bad'))->toThrow(InvalidArgumentException::class);
    });

    test('ARDA6-FR-ASM-007: toArray, only, except, and merge shape the payload', function (): void {
        $dto = new ScoreIndicatorData(competencyId: 'c-1', indicatorId: 'i-1', score: 80);

        expect($dto->toArray())->toBe(['competencyId' => 'c-1', 'indicatorId' => 'i-1', 'score' => 80.0]);
        expect($dto->only('score'))->toBe(['score' => 80.0]);
        expect($dto->except('score'))->toBe(['competencyId' => 'c-1', 'indicatorId' => 'i-1']);

        $merged = $dto->merge(['score' => 95]);

        expect($merged->score)->toBe(95.0);
        expect($dto->score)->toBe(80.0);
    });
});

describe('ARDA6: UpdateAssessmentScoresData DTO', function (): void {
    test('ARDA6-FR-ASM-007: fromArray maps exact keys with an explicit nullable score', function (): void {
        $dto = UpdateAssessmentScoresData::fromArray(['competencyId' => 'c-1', 'indicatorId' => 'i-1', 'score' => null]);

        expect($dto->competencyId)->toBe('c-1');
        expect($dto->indicatorId)->toBe('i-1');
        expect($dto->score)->toBeNull();
    });

    test('ARDA6-FR-ASM-007: fromArray accepts snake_case keys', function (): void {
        $dto = UpdateAssessmentScoresData::fromArray(['competency_id' => 'c-5', 'indicator_id' => 'i-5', 'score' => 88]);

        expect($dto->competencyId)->toBe('c-5');
        expect($dto->score)->toBe(88.0);
    });

    test('ARDA6-FR-ASM-007: fromArray throws when the indicator reference is missing', function (): void {
        expect(fn (): UpdateAssessmentScoresData => UpdateAssessmentScoresData::fromArray(['competencyId' => 'c']))
            ->toThrow(InvalidArgumentException::class, 'indicatorId');
    });

    test('ARDA6-FR-ASM-007: from accepts arrays and arrayables, rejects scalars', function (): void {
        $payload = ['competencyId' => 'c', 'indicatorId' => 'i', 'score' => null];

        expect(UpdateAssessmentScoresData::from($payload)->score)->toBeNull();

        $source = new class
        {
            public function toArray(): array
            {
                return ['competencyId' => 'c', 'indicatorId' => 'i2', 'score' => 40];
            }
        };

        expect(UpdateAssessmentScoresData::from($source)->score)->toBe(40.0);
        expect(fn (): UpdateAssessmentScoresData => UpdateAssessmentScoresData::from(42))->toThrow(InvalidArgumentException::class);
    });

    test('ARDA6-FR-ASM-007: toArray, only, except, and merge shape the payload', function (): void {
        $dto = new UpdateAssessmentScoresData(competencyId: 'c-1', indicatorId: 'i-1', score: 75);

        expect($dto->toArray())->toBe(['competencyId' => 'c-1', 'indicatorId' => 'i-1', 'score' => 75.0]);
        expect($dto->only('indicatorId', 'score'))->toBe(['indicatorId' => 'i-1', 'score' => 75.0]);
        expect($dto->except('score'))->toBe(['competencyId' => 'c-1', 'indicatorId' => 'i-1']);

        $merged = $dto->merge(['score' => null]);

        expect($merged->score)->toBeNull();
        expect($dto->score)->toBe(75.0);
    });
});
