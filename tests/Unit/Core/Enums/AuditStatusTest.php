<?php

declare(strict_types=1);

use App\Core\Contracts\LabelEnum;
use App\Core\Enums\AuditStatus;

test('AuditStatus implements LabelEnum', function () {
    $ref = new ReflectionClass(AuditStatus::class);
    expect($ref->implementsInterface(LabelEnum::class))->toBeTrue();
});

test('AuditStatus has expected statuses', function () {
    expect(AuditStatus::PASS->value)->toBe('pass');
    expect(AuditStatus::FAIL->value)->toBe('fail');
    expect(AuditStatus::WARN->value)->toBe('warn');
});
