<?php

declare(strict_types=1);

use App\Core\Contracts\LabelEnum;
use App\Enums\CsvRowResult;

test('csv row result has expected cases', function () {
    expect(CsvRowResult::cases())->toHaveCount(2);
    expect(CsvRowResult::CREATED->value)->toBe('created');
    expect(CsvRowResult::SKIPPED->value)->toBe('skipped');
});

test('csv row result implements label enum', function () {
    expect(CsvRowResult::CREATED)->toBeInstanceOf(LabelEnum::class);
});

test('csv row result provides labels', function () {
    expect(CsvRowResult::CREATED->label())->toBeString();
    expect(CsvRowResult::SKIPPED->label())->toBeString();
});

test('csv row result is string backed', function () {
    expect(CsvRowResult::tryFrom('created'))->toBe(CsvRowResult::CREATED);
    expect(CsvRowResult::tryFrom('skipped'))->toBe(CsvRowResult::SKIPPED);
    expect(CsvRowResult::tryFrom('unknown'))->toBeNull();
});
