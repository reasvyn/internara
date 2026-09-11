<?php

declare(strict_types=1);

use App\Modules\Assignment\Domain\Submission\Data\GradeSubmissionData;
use App\Modules\Assignment\Domain\Submission\Data\SubmitAssignmentData;

describe('T657Z: SubmitAssignmentData DTO', function (): void {
    test('T657Z-FR-SUBM-013: fromArray maps the submitted content', function (): void {
        $dto = SubmitAssignmentData::fromArray(['content' => 'Laporan kegiatan minggu pertama.']);

        expect($dto->content)->toBe('Laporan kegiatan minggu pertama.');
    });

    test('T657Z-FR-SUBM-013: fromArray throws when content is missing', function (): void {
        expect(fn (): SubmitAssignmentData => SubmitAssignmentData::fromArray([]))
            ->toThrow(InvalidArgumentException::class, 'content');
    });

    test('T657Z-FR-SUBM-013: from accepts arrays and arrayables, rejects scalars', function (): void {
        expect(SubmitAssignmentData::from(['content' => 'Isi'])->content)->toBe('Isi');

        $source = new class
        {
            public function toArray(): array
            {
                return ['content' => 'Isi arrayable'];
            }
        };

        expect(SubmitAssignmentData::from($source)->content)->toBe('Isi arrayable');
        expect(fn (): SubmitAssignmentData => SubmitAssignmentData::from(42))->toThrow(InvalidArgumentException::class);
    });

    test('T657Z-FR-SUBM-013: toArray, only, except, and merge shape the payload', function (): void {
        $dto = new SubmitAssignmentData(content: 'Draf awal');

        expect($dto->toArray())->toBe(['content' => 'Draf awal']);
        expect($dto->only('content'))->toBe(['content' => 'Draf awal']);
        expect($dto->except('content'))->toBe([]);

        $merged = $dto->merge(['content' => 'Revisi final']);

        expect($merged->content)->toBe('Revisi final');
        expect($dto->content)->toBe('Draf awal');
    });
});

describe('T657Z: GradeSubmissionData DTO', function (): void {
    test('T657Z-FR-GRADE-002: fromArray maps the score with empty feedback default', function (): void {
        $dto = GradeSubmissionData::fromArray(['score' => 85]);

        expect($dto->score)->toBe(85);
        expect($dto->feedback)->toBeNull();
    });

    test('T657Z-FR-GRADE-002: fromArray throws when the score is missing', function (): void {
        expect(fn (): GradeSubmissionData => GradeSubmissionData::fromArray(['feedback' => 'Bagus']))
            ->toThrow(InvalidArgumentException::class, 'score');
    });

    test('T657Z-FR-GRADE-002: from accepts arrays and arrayables, rejects scalars', function (): void {
        expect(GradeSubmissionData::from(['score' => 70, 'feedback' => 'Ok'])->feedback)->toBe('Ok');

        $source = new class
        {
            public function toArray(): array
            {
                return ['score' => 60];
            }
        };

        expect(GradeSubmissionData::from($source)->score)->toBe(60);
        expect(fn (): GradeSubmissionData => GradeSubmissionData::from(null))->toThrow(InvalidArgumentException::class);
    });

    test('T657Z-FR-GRADE-003: toArray, only, except, and merge shape the payload', function (): void {
        $dto = new GradeSubmissionData(score: 90, feedback: 'Sangat baik');

        expect($dto->toArray())->toBe(['score' => 90, 'feedback' => 'Sangat baik']);
        expect($dto->only('score'))->toBe(['score' => 90]);
        expect($dto->except('feedback'))->toBe(['score' => 90]);

        $merged = $dto->merge(['score' => 95]);

        expect($merged->score)->toBe(95);
        expect($dto->score)->toBe(90);
    });
});
