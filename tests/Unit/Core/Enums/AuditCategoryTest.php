<?php

declare(strict_types=1);

use App\Domain\Core\Contracts\LabelEnum;
use App\Domain\Core\Enums\AuditCategory;

describe('AuditCategory', function () {
    it('is string-backed', function () {
        expect(AuditCategory::REQUIREMENTS->value)->toBe('requirements');
    });

    it('implements LabelEnum', function () {
        expect(AuditCategory::REQUIREMENTS)->toBeInstanceOf(LabelEnum::class);
    });

    it('has all expected cases', function () {
        $cases = AuditCategory::cases();

        expect($cases)->toHaveCount(5)
            ->and(collect($cases)->map(fn ($c) => $c->value)->values()->toArray())
            ->toBe(['requirements', 'permissions', 'database', 'terminal', 'recommendations']);
    });

    it('returns a label', function () {
        expect(AuditCategory::REQUIREMENTS->label())->toBeString()->not->toBeEmpty();
    });

    it('identifies critical categories', function () {
        expect(AuditCategory::REQUIREMENTS->isCritical())->toBeTrue()
            ->and(AuditCategory::PERMISSIONS->isCritical())->toBeTrue()
            ->and(AuditCategory::DATABASE->isCritical())->toBeTrue();
    });

    it('identifies non-critical categories', function () {
        expect(AuditCategory::TERMINAL->isCritical())->toBeFalse()
            ->and(AuditCategory::RECOMMENDATIONS->isCritical())->toBeFalse();
    });
});
