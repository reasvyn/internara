<?php

declare(strict_types=1);

use App\Core\Contracts\LabelEnum;
use App\Core\Enums\AuditStatus;

it('FR-D4: every case has a non-empty translated label', function () {
    foreach (AuditStatus::cases() as $case) {
        expect($case->label())->toBeString()->not->toBeEmpty();
    }
});

it('FR-D4: implements the LabelEnum contract', function () {
    expect(AuditStatus::class)->toImplement(LabelEnum::class);
});

it('FR-D4: symbol returns a status glyph for every case', function () {
    expect(AuditStatus::PASS->symbol())->toBe('✓');
    expect(AuditStatus::FAIL->symbol())->toBe('✗');
    expect(AuditStatus::WARN->symbol())->toBe('⚠');
});
