<?php

declare(strict_types=1);

use App\Domain\Shared\Enums\CsvRowResult;

describe('CsvRowResult', function () {
    it('is string-backed enum', function () {
        expect(CsvRowResult::CREATED->value)->toBe('created');
        expect(CsvRowResult::SKIPPED->value)->toBe('skipped');
    });

    it('has both expected cases', function () {
        $cases = CsvRowResult::cases();

        expect($cases)->toHaveCount(2);
        expect($cases)->toContain(CsvRowResult::CREATED);
        expect($cases)->toContain(CsvRowResult::SKIPPED);
    });

    it('returns label via translation helper', function () {
        expect(CsvRowResult::CREATED->label())->toBe(__('shared.csv.created'));
        expect(CsvRowResult::SKIPPED->label())->toBe(__('shared.csv.skipped'));
    });
});
