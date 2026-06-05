<?php

declare(strict_types=1);

use App\Core\Contracts\LabelEnum;
use App\Core\Enums\CsvRowResult;

test('CsvRowResult implements LabelEnum', function () {
    $ref = new ReflectionClass(CsvRowResult::class);
    expect($ref->implementsInterface(LabelEnum::class))->toBeTrue();
});

test('CsvRowResult has expected cases', function () {
    expect(CsvRowResult::CREATED->value)->toBe('created');
    expect(CsvRowResult::SKIPPED->value)->toBe('skipped');
});

test('CsvRowResult provides labels', function () {
    expect(CsvRowResult::CREATED->label())->toBeString();
    expect(CsvRowResult::SKIPPED->label())->toBeString();
});
