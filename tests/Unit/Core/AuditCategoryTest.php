<?php

declare(strict_types=1);

use App\Domain\Core\Enums\AuditCategory;

describe('AuditCategory', function () {
    it('is string-backed with 5 cases', function () {
        expect(AuditCategory::cases())->toHaveCount(5);
    });

    it('has correct values', function () {
        expect(AuditCategory::REQUIREMENTS->value)->toBe('requirements')
            ->and(AuditCategory::PERMISSIONS->value)->toBe('permissions')
            ->and(AuditCategory::DATABASE->value)->toBe('database')
            ->and(AuditCategory::TERMINAL->value)->toBe('terminal')
            ->and(AuditCategory::RECOMMENDATIONS->value)->toBe('recommendations');
    });

    it('REQUIREMENTS, PERMISSIONS, DATABASE are critical', function () {
        expect(AuditCategory::REQUIREMENTS->isCritical())->toBeTrue()
            ->and(AuditCategory::PERMISSIONS->isCritical())->toBeTrue()
            ->and(AuditCategory::DATABASE->isCritical())->toBeTrue();
    });

    it('TERMINAL and RECOMMENDATIONS are not critical', function () {
        expect(AuditCategory::TERMINAL->isCritical())->toBeFalse()
            ->and(AuditCategory::RECOMMENDATIONS->isCritical())->toBeFalse();
    });

    it('returns a label string', function () {
        expect(AuditCategory::REQUIREMENTS->label())->toBeString();
    });
});
