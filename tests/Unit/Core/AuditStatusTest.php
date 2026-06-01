<?php

declare(strict_types=1);

use App\Domain\Core\Contracts\LabelEnum;
use App\Domain\Core\Enums\AuditStatus;

describe('AuditStatus', function () {
    it('is string-backed with 3 cases', function () {
        expect(AuditStatus::cases())->toHaveCount(3);
    });

    it('has correct values', function () {
        expect(AuditStatus::PASS->value)->toBe('pass')
            ->and(AuditStatus::FAIL->value)->toBe('fail')
            ->and(AuditStatus::WARN->value)->toBe('warn');
    });

    it('implements LabelEnum', function () {
        expect(AuditStatus::PASS)->toBeInstanceOf(LabelEnum::class);
    });

    it('returns labels', function () {
        expect(AuditStatus::PASS->label())->toBe('Pass')
            ->and(AuditStatus::FAIL->label())->toBe('Fail')
            ->and(AuditStatus::WARN->label())->toBe('Warn');
    });

    it('returns symbols', function () {
        expect(AuditStatus::PASS->symbol())->not->toBeEmpty()
            ->and(AuditStatus::FAIL->symbol())->not->toBeEmpty()
            ->and(AuditStatus::WARN->symbol())->not->toBeEmpty();
    });
});
