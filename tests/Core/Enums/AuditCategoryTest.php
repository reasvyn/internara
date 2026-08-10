<?php

declare(strict_types=1);

use App\Core\Contracts\LabelEnum;
use App\Core\Enums\AuditCategory;

it('FR-D3: every case has a non-empty translated label', function () {
    foreach (AuditCategory::cases() as $case) {
        expect($case->label())->toBeString()->not->toBeEmpty();
    }
});

it('FR-D3: implements the LabelEnum contract', function () {
    expect(AuditCategory::class)->toImplement(LabelEnum::class);
});

it('FR-D3: marks critical categories as critical', function () {
    expect(AuditCategory::REQUIREMENTS->isCritical())->toBeTrue();
    expect(AuditCategory::PERMISSIONS->isCritical())->toBeTrue();
    expect(AuditCategory::DATABASE->isCritical())->toBeTrue();
    expect(AuditCategory::TERMINAL->isCritical())->toBeFalse();
    expect(AuditCategory::RECOMMENDATIONS->isCritical())->toBeFalse();
});
