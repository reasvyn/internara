<?php

declare(strict_types=1);

use App\Domain\Core\Contracts\LabelEnum;
use App\Domain\Core\Enums\AuditStatus;

describe('AuditStatus', function () {
    it('is string-backed', function () {
        expect(AuditStatus::PASS->value)->toBe('pass');
    });

    it('implements LabelEnum', function () {
        expect(AuditStatus::PASS)->toBeInstanceOf(LabelEnum::class);
    });

    it('has all expected cases', function () {
        $cases = AuditStatus::cases();

        expect($cases)->toHaveCount(3)
            ->and(collect($cases)->map(fn ($c) => $c->value)->values()->toArray())
            ->toBe(['pass', 'fail', 'warn']);
    });

    it('returns a label', function () {
        expect(AuditStatus::PASS->label())->toBeString()->not->toBeEmpty();
    });

    it('returns correct symbols', function () {
        expect(AuditStatus::PASS->symbol())->toBe('✓')
            ->and(AuditStatus::FAIL->symbol())->toBe('✗')
            ->and(AuditStatus::WARN->symbol())->toBe('⚠');
    });
});
