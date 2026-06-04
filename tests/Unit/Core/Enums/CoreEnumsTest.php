<?php

declare(strict_types=1);

use App\Domain\Core\Contracts\LabelEnum;
use App\Domain\Core\Enums\AuditCategory;
use App\Domain\Core\Enums\AuditStatus;
use App\Domain\Core\Enums\CsvRowResult;

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

test('AuditCategory implements LabelEnum', function () {
    $ref = new ReflectionClass(AuditCategory::class);
    expect($ref->implementsInterface(LabelEnum::class))->toBeTrue();
});

test('AuditCategory has expected categories', function () {
    expect(AuditCategory::REQUIREMENTS->value)->toBe('requirements');
    expect(AuditCategory::PERMISSIONS->value)->toBe('permissions');
    expect(AuditCategory::DATABASE->value)->toBe('database');
    expect(AuditCategory::TERMINAL->value)->toBe('terminal');
    expect(AuditCategory::RECOMMENDATIONS->value)->toBe('recommendations');
});

test('AuditStatus implements LabelEnum', function () {
    $ref = new ReflectionClass(AuditStatus::class);
    expect($ref->implementsInterface(LabelEnum::class))->toBeTrue();
});

test('AuditStatus has expected statuses', function () {
    expect(AuditStatus::PASS->value)->toBe('pass');
    expect(AuditStatus::FAIL->value)->toBe('fail');
    expect(AuditStatus::WARN->value)->toBe('warn');
});
