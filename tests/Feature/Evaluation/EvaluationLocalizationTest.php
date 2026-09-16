<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

describe('AXKZW evaluation localization', function (): void {
    test('AXKZW-FR-EVAL-021: builder, submission, and results strings resolve in both English and Indonesian', function (): void {
        $keys = [
            'evaluation.page_title' => ['Evaluations', 'Evaluasi'],
            'evaluation.submit' => ['Submit', 'Kirim'],
            'evaluation.cancel' => ['Cancel', 'Batal'],
            'evaluation.submit_success' => ['Evaluation submitted successfully.', 'Evaluasi berhasil dikirim.'],
            'evaluation.overall_score' => ['Overall Score', 'Skor Keseluruhan'],
            'evaluation.mentor' => ['Mentor', 'Mentor'],
        ];

        foreach ($keys as $key => [$english, $indonesian]) {
            expect(trans($key, [], 'en'))->toBe($english)
                ->and(trans($key, [], 'id'))->toBe($indonesian);
        }
    });

    test('AXKZW-FR-EVAL-021: criteria labels are mirrored across both locales', function (): void {
        $criteria = [
            'evaluation.criteria.guidance_quality' => ['Guidance Quality', 'Kualitas Bimbingan'],
            'evaluation.criteria.communication' => ['Communication', 'Komunikasi'],
            'evaluation.criteria.workplace_safety' => ['Workplace Safety', 'Keselamatan Kerja'],
        ];

        foreach ($criteria as $key => [$english, $indonesian]) {
            expect(trans($key, [], 'en'))->toBe($english)
                ->and(trans($key, [], 'id'))->toBe($indonesian)
                ->and(trans($key, [], 'en'))->not->toBe($key);
        }
    });
});
