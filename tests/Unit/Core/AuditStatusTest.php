<?php

declare(strict_types=1);

use App\Domain\Core\Contracts\LabelEnum;
use App\Domain\Core\Enums\AuditStatus;

describe('AuditStatus', function () {
    it('is string-backed', function () {
        expect(AuditStatus::Pass->value)->toBe('pass')
            ->and(AuditStatus::Fail->value)->toBe('fail')
            ->and(AuditStatus::Warn->value)->toBe('warn');
    });

    it('returns labels', function () {
        expect(AuditStatus::Pass->label())->toBe('Pass')
            ->and(AuditStatus::Fail->label())->toBe('Fail')
            ->and(AuditStatus::Warn->label())->toBe('Warn');
    });

    it('returns symbols', function () {
        expect(AuditStatus::Pass->symbol())->not->toBeEmpty()
            ->and(AuditStatus::Fail->symbol())->not->toBeEmpty()
            ->and(AuditStatus::Warn->symbol())->not->toBeEmpty();
    });

    it('implements LabelEnum', function () {
        expect(AuditStatus::Pass)->toBeInstanceOf(LabelEnum::class);
    });
});
