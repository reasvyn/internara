<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

describe('7H5D6: bilingual document strings', function () {
    test('7H5D6-FR-OFFD-022: document interface strings resolve in English and Indonesian', function (): void {
        app()->setLocale('en');
        expect(__('document.template_saved'))->toBe('Template saved successfully.')
            ->and(__('document.report_deleted'))->toBe('Report deleted.')
            ->and(__('document.generate'))->toBe('Generate');

        app()->setLocale('id');
        expect(__('document.template_saved'))->toBe('Template berhasil disimpan.')
            ->and(__('document.report_deleted'))->toBe('Laporan berhasil dihapus.')
            ->and(__('document.generate'))->toBe('Buat');
    });

    test('7H5D6-FR-OFFD-022: every document key ships in both languages with no gaps', function (): void {
        $en = require base_path('lang/en/document.php');
        $id = require base_path('lang/id/document.php');

        expect($en)->not->toBeEmpty()
            ->and($id)->not->toBeEmpty()
            ->and(array_diff_key($en, $id))->toBe([])
            ->and(array_diff_key($id, $en))->toBe([]);

        foreach ($en as $key => $value) {
            expect(is_string($value) && $value !== '')->toBeTrue("en document.{$key} must be a non-empty string");
            expect(is_string($id[$key]) && $id[$key] !== '')->toBeTrue("id document.{$key} must be a non-empty string");
        }
    });
});
