<?php

declare(strict_types=1);

use App\Domain\Shared\Enums\CsvRowResult;

describe('CsvRowResult', function () {
    it('has created case', function () {
        expect(CsvRowResult::CREATED->value)->toBe('created');
    });

    it('has skipped case', function () {
        expect(CsvRowResult::SKIPPED->value)->toBe('skipped');
    });

    it('is string backed', function () {
        expect(CsvRowResult::tryFrom('created'))->toBe(CsvRowResult::CREATED)
            ->and(CsvRowResult::tryFrom('skipped'))->toBe(CsvRowResult::SKIPPED)
            ->and(CsvRowResult::tryFrom('invalid'))->toBeNull();
    });
});
