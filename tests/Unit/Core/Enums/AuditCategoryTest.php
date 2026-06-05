<?php

declare(strict_types=1);

use App\Core\Contracts\LabelEnum;
use App\Core\Enums\AuditCategory;

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
