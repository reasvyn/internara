<?php

declare(strict_types=1);

use App\Domain\Core\Enums\AuditCategory;

describe('AuditCategory', function () {
    it('is string-backed', function () {
        expect(AuditCategory::REQUIREMENTS->value)->toBe('requirements')
            ->and(AuditCategory::DATABASE->value)->toBe('database');
    });

    it('returns labels', function () {
        expect(AuditCategory::REQUIREMENTS->label())->toBeString();
    });

    it('has all expected cases', function () {
        $cases = AuditCategory::cases();

        expect($cases)->toHaveCount(5)
            ->and(AuditCategory::REQUIREMENTS)->toBeInstanceOf(AuditCategory::class)
            ->and(AuditCategory::PERMISSIONS)->toBeInstanceOf(AuditCategory::class)
            ->and(AuditCategory::DATABASE)->toBeInstanceOf(AuditCategory::class)
            ->and(AuditCategory::TERMINAL)->toBeInstanceOf(AuditCategory::class)
            ->and(AuditCategory::RECOMMENDATIONS)->toBeInstanceOf(AuditCategory::class);
    });

    it('identifies critical categories', function () {
        expect(AuditCategory::REQUIREMENTS->isCritical())->toBeTrue()
            ->and(AuditCategory::PERMISSIONS->isCritical())->toBeTrue()
            ->and(AuditCategory::DATABASE->isCritical())->toBeTrue()
            ->and(AuditCategory::TERMINAL->isCritical())->toBeFalse()
            ->and(AuditCategory::RECOMMENDATIONS->isCritical())->toBeFalse();
    });
});
