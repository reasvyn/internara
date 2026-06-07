<?php

declare(strict_types=1);

use App\Core\Contracts\LabelEnum;
use App\Enums\AuditStatus;

test('audit status has expected cases', function () {
    expect(AuditStatus::cases())->toHaveCount(3);
    expect(AuditStatus::PASS->value)->toBe('pass');
    expect(AuditStatus::FAIL->value)->toBe('fail');
    expect(AuditStatus::WARN->value)->toBe('warn');
});

test('audit status implements label enum', function () {
    expect(AuditStatus::PASS)->toBeInstanceOf(LabelEnum::class);
});

test('audit status provides labels', function () {
    expect(AuditStatus::PASS->label())->toBeString();
    expect(AuditStatus::FAIL->label())->toBeString();
    expect(AuditStatus::WARN->label())->toBeString();
});

test('audit status provides symbols', function () {
    expect(AuditStatus::PASS->symbol())->toBe('✓');
    expect(AuditStatus::FAIL->symbol())->toBe('✗');
    expect(AuditStatus::WARN->symbol())->toBe('⚠');
});

test('audit status is string backed', function () {
    expect(AuditStatus::tryFrom('pass'))->toBe(AuditStatus::PASS);
    expect(AuditStatus::tryFrom('fail'))->toBe(AuditStatus::FAIL);
    expect(AuditStatus::tryFrom('warn'))->toBe(AuditStatus::WARN);
    expect(AuditStatus::tryFrom('unknown'))->toBeNull();
});
