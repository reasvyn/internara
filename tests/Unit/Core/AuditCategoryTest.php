<?php

declare(strict_types=1);

use App\Domain\Core\Enums\AuditCategory;

describe('AuditCategory', function () {
    it('is string-backed', function () {
        expect(AuditCategory::Requirements->value)->toBe('requirements')
            ->and(AuditCategory::Database->value)->toBe('database');
    });

    it('returns labels', function () {
        expect(AuditCategory::Requirements->label())->toBeString();
    });

    it('has all expected cases', function () {
        $cases = AuditCategory::cases();

        expect($cases)->toHaveCount(5)
            ->and(AuditCategory::Requirements)->toBeInstanceOf(AuditCategory::class)
            ->and(AuditCategory::Permissions)->toBeInstanceOf(AuditCategory::class)
            ->and(AuditCategory::Database)->toBeInstanceOf(AuditCategory::class)
            ->and(AuditCategory::Terminal)->toBeInstanceOf(AuditCategory::class)
            ->and(AuditCategory::Recommendations)->toBeInstanceOf(AuditCategory::class);
    });

    it('identifies critical categories', function () {
        expect(AuditCategory::Requirements->isCritical())->toBeTrue()
            ->and(AuditCategory::Permissions->isCritical())->toBeTrue()
            ->and(AuditCategory::Database->isCritical())->toBeTrue()
            ->and(AuditCategory::Terminal->isCritical())->toBeFalse()
            ->and(AuditCategory::Recommendations->isCritical())->toBeFalse();
    });
});
