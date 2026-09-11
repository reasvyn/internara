<?php

declare(strict_types=1);

use App\Modules\Core\Enums\AuditCategory;

describe('AuditCategory enum', function (): void {
    test('cases carry the specified backing values: cases carry the specified backing values', function (): void {
        expect(AuditCategory::REQUIREMENTS->value)->toBe('requirements');
        expect(AuditCategory::from('requirements'))->toBe(AuditCategory::REQUIREMENTS);
        expect(AuditCategory::PERMISSIONS->value)->toBe('permissions');
        expect(AuditCategory::from('permissions'))->toBe(AuditCategory::PERMISSIONS);
        expect(AuditCategory::DATABASE->value)->toBe('database');
        expect(AuditCategory::from('database'))->toBe(AuditCategory::DATABASE);
        expect(AuditCategory::TERMINAL->value)->toBe('terminal');
        expect(AuditCategory::from('terminal'))->toBe(AuditCategory::TERMINAL);
        expect(AuditCategory::RECOMMENDATIONS->value)->toBe('recommendations');
        expect(AuditCategory::from('recommendations'))->toBe(AuditCategory::RECOMMENDATIONS);
        expect(AuditCategory::cases())->toHaveCount(5);
        expect(AuditCategory::tryFrom('no-such-value'))->toBeNull();
    });
    test('labels resolve in both locales: labels resolve in English', function (): void {
        app()->setLocale('en');

        expect(AuditCategory::REQUIREMENTS->label())->toBe('Requirements');
        expect(AuditCategory::PERMISSIONS->label())->toBe('Permissions');
        expect(AuditCategory::DATABASE->label())->toBe('Database');
        expect(AuditCategory::TERMINAL->label())->toBe('Terminal');
        expect(AuditCategory::RECOMMENDATIONS->label())->toBe('Recommendations');
    });

    test('labels resolve in both locales: labels resolve in Indonesian', function (): void {
        app()->setLocale('id');

        expect(AuditCategory::REQUIREMENTS->label())->toBe('Persyaratan');
        expect(AuditCategory::PERMISSIONS->label())->toBe('Izin');
        expect(AuditCategory::DATABASE->label())->toBe('Database');
        expect(AuditCategory::TERMINAL->label())->toBe('Terminal');
        expect(AuditCategory::RECOMMENDATIONS->label())->toBe('Rekomendasi');
    });
    test('requirements, permissions, and database are critical while terminal and recommendations are not', function (): void {
        expect(AuditCategory::REQUIREMENTS->isCritical())->toBeTrue();
        expect(AuditCategory::PERMISSIONS->isCritical())->toBeTrue();
        expect(AuditCategory::DATABASE->isCritical())->toBeTrue();
        expect(AuditCategory::TERMINAL->isCritical())->toBeFalse();
        expect(AuditCategory::RECOMMENDATIONS->isCritical())->toBeFalse();
    });
});
