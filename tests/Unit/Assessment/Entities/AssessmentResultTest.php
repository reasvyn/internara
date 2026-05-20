<?php

declare(strict_types=1);

use App\Domain\Assessment\Entities\AssessmentResult;
use App\Domain\Core\Entities\BaseEntity;
use Carbon\Carbon;

describe('AssessmentResult entity', function () {
    it('detects finalized', function () {
        $entity = new AssessmentResult(finalizedAt: Carbon::now(), content: 85.0, score: 85.0);

        expect($entity->isFinalized())->toBeTrue();
    });

    it('detects not finalized', function () {
        $entity = new AssessmentResult(finalizedAt: null, content: 0.0, score: 0.0);

        expect($entity->isFinalized())->toBeFalse();
    });

    it('calculates total score from content array', function () {
        $content = [
            'competencies' => [
                ['indicators' => [1 => 80.0, 2 => 90.0]],
                ['indicators' => [3 => 70.0]],
            ],
        ];
        $entity = new AssessmentResult(finalizedAt: null, content: $content, score: 0.0);

        expect($entity->calculateTotalScore())->toBe(240.0);
    });

    it('returns direct score when content is not array', function () {
        $entity = new AssessmentResult(finalizedAt: null, content: 85.0, score: 85.0);

        expect($entity->calculateTotalScore())->toBe(85.0);
    });

    it('is final readonly', function () {
        $ref = new ReflectionClass(AssessmentResult::class);

        expect($ref->isFinal())->toBeTrue()
            ->and($ref->isReadOnly())->toBeTrue();
    });

    it('extends BaseEntity', function () {
        expect(AssessmentResult::class)->toExtend(BaseEntity::class);
    });
});
