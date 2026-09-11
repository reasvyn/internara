<?php

declare(strict_types=1);

use App\Modules\Assessment\Entities\AssessmentResult;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

final class AssessmentResultModelDouble extends Model
{
    protected $guarded = [];
}

describe('ARDA6: assessment result', function (): void {
    test('ARDA6-FR-ASM-015: fromModel bridges persistence without persisting', function (): void {
        $model = new AssessmentResultModelDouble([
            'finalized_at' => Carbon::parse('2026-06-01 10:00:00'),
            'scores_data' => ['competencies' => []],
            'score' => 87.5,
        ]);

        $result = AssessmentResult::fromModel($model);

        expect($result->isFinalized())->toBeTrue();
        expect($model->exists)->toBeFalse();
    });

    test('ARDA6-FR-ASM-015: fromArray hydrates the triplet and rejects a missing score', function (): void {
        $result = AssessmentResult::fromArray([
            'finalizedAt' => null,
            'scoresData' => [],
            'score' => 0.0,
        ]);

        expect($result->isFinalized())->toBeFalse();

        expect(fn (): AssessmentResult => AssessmentResult::fromArray(['finalizedAt' => null, 'scoresData' => []]))
            ->toThrow(InvalidArgumentException::class, 'score');
    });

    test('ARDA6-FR-ASM-015: isFinalized is true only when a finalization timestamp exists', function (): void {
        $finalized = AssessmentResult::fromArray([
            'finalizedAt' => Carbon::parse('2026-06-01 10:00:00'),
            'scoresData' => [],
            'score' => 90.0,
        ]);
        $draft = AssessmentResult::fromArray(['finalizedAt' => null, 'scoresData' => [], 'score' => 0.0]);

        expect($finalized->isFinalized())->toBeTrue();
        expect($draft->isFinalized())->toBeFalse();
    });

    test('sums every indicator score across competencies', function (): void {
        $result = AssessmentResult::fromArray([
            'finalizedAt' => null,
            'scoresData' => ['competencies' => [
                ['indicators' => [80.0, 90.0]],
                ['indicators' => [70.0]],
                ['other' => 'ignored'],
            ]],
            'score' => 0.0,
        ]);

        expect($result->calculateTotalScore())->toBe(240.0);
    });

    test('returns zero for empty competency payloads', function (): void {
        $empty = AssessmentResult::fromArray(['finalizedAt' => null, 'scoresData' => [], 'score' => 0.0]);
        $noCompetencies = AssessmentResult::fromArray(['finalizedAt' => null, 'scoresData' => ['auto' => []], 'score' => 0.0]);

        expect($empty->calculateTotalScore())->toBe(0.0);
        expect($noCompetencies->calculateTotalScore())->toBe(0.0);
    });

    test('falls back to the stored score when scores data is a plain float', function (): void {
        $result = AssessmentResult::fromArray(['finalizedAt' => null, 'scoresData' => 75.5, 'score' => 75.5]);

        expect($result->calculateTotalScore())->toBe(75.5);
    });

    test('ARDA6-FR-ASM-018: equals compares finalized snapshots by value', function (): void {
        $finalizedAt = Carbon::parse('2026-06-01 10:00:00');
        $a = AssessmentResult::fromArray(['finalizedAt' => $finalizedAt, 'scoresData' => [], 'score' => 90.0]);
        $b = AssessmentResult::fromArray(['finalizedAt' => Carbon::parse('2026-06-01 10:00:00'), 'scoresData' => [], 'score' => 90.0]);
        $c = AssessmentResult::fromArray(['finalizedAt' => null, 'scoresData' => [], 'score' => 90.0]);

        expect($a->equals($b))->toBeTrue();
        expect($a->equals($c))->toBeFalse();
    });
});
