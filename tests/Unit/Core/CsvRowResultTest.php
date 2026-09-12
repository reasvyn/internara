<?php

declare(strict_types=1);

use App\Modules\Core\Enums\CsvRowResult;

describe('O2KCR: CsvRowResult enum', function (): void {
    test('O2KCR-FR-CSV-007: cases carry the specified backing values', function (): void {
        expect(CsvRowResult::CREATED->value)->toBe('created');
        expect(CsvRowResult::from('created'))->toBe(CsvRowResult::CREATED);
        expect(CsvRowResult::SKIPPED->value)->toBe('skipped');
        expect(CsvRowResult::from('skipped'))->toBe(CsvRowResult::SKIPPED);
        expect(CsvRowResult::FAILED->value)->toBe('failed');
        expect(CsvRowResult::from('failed'))->toBe(CsvRowResult::FAILED);
        expect(CsvRowResult::cases())->toHaveCount(3);
        expect(CsvRowResult::tryFrom('no-such-value'))->toBeNull();
    });
    test('O2KCR-FR-CSV-007: labels resolve in English', function (): void {
        app()->setLocale('en');

        expect(CsvRowResult::CREATED->label())->toBe('Created');
        expect(CsvRowResult::SKIPPED->label())->toBe('Skipped');
        expect(CsvRowResult::FAILED->label())->toBe('Failed');
    });

    test('O2KCR-FR-CSV-007: labels resolve in Indonesian', function (): void {
        app()->setLocale('id');

        expect(CsvRowResult::CREATED->label())->toBe('Dibuat');
        expect(CsvRowResult::SKIPPED->label())->toBe('Dilewati');
        expect(CsvRowResult::FAILED->label())->toBe('Gagal');
    });
});
