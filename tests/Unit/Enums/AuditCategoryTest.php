<?php

declare(strict_types=1);

use App\Core\Contracts\LabelEnum;
use App\Enums\AuditCategory;

test('audit category has expected cases', function () {
    expect(AuditCategory::cases())->toHaveCount(5);
    expect(AuditCategory::REQUIREMENTS->value)->toBe('requirements');
    expect(AuditCategory::PERMISSIONS->value)->toBe('permissions');
    expect(AuditCategory::DATABASE->value)->toBe('database');
    expect(AuditCategory::TERMINAL->value)->toBe('terminal');
    expect(AuditCategory::RECOMMENDATIONS->value)->toBe('recommendations');
});

test('audit category implements label enum', function () {
    expect(AuditCategory::REQUIREMENTS)->toBeInstanceOf(LabelEnum::class);
});

test('audit category provides labels', function () {
    expect(AuditCategory::REQUIREMENTS->label())->toBeString();
    expect(AuditCategory::PERMISSIONS->label())->toBeString();
    expect(AuditCategory::DATABASE->label())->toBeString();
    expect(AuditCategory::TERMINAL->label())->toBeString();
    expect(AuditCategory::RECOMMENDATIONS->label())->toBeString();
});

test('isCritical returns true for requirements permissions and database', function () {
    expect(AuditCategory::REQUIREMENTS->isCritical())->toBeTrue();
    expect(AuditCategory::PERMISSIONS->isCritical())->toBeTrue();
    expect(AuditCategory::DATABASE->isCritical())->toBeTrue();
});

test('isCritical returns false for terminal and recommendations', function () {
    expect(AuditCategory::TERMINAL->isCritical())->toBeFalse();
    expect(AuditCategory::RECOMMENDATIONS->isCritical())->toBeFalse();
});

test('audit category is string backed', function () {
    expect(AuditCategory::tryFrom('database'))->toBe(AuditCategory::DATABASE);
    expect(AuditCategory::tryFrom('unknown'))->toBeNull();
});
