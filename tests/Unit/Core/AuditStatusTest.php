<?php

declare(strict_types=1);

use App\Modules\Core\Enums\AuditStatus;

describe('AuditStatus enum', function (): void {
    test('cases carry the specified backing values: cases carry the specified backing values', function (): void {
        expect(AuditStatus::PASS->value)->toBe('pass');
        expect(AuditStatus::from('pass'))->toBe(AuditStatus::PASS);
        expect(AuditStatus::FAIL->value)->toBe('fail');
        expect(AuditStatus::from('fail'))->toBe(AuditStatus::FAIL);
        expect(AuditStatus::WARN->value)->toBe('warn');
        expect(AuditStatus::from('warn'))->toBe(AuditStatus::WARN);
        expect(AuditStatus::cases())->toHaveCount(3);
        expect(AuditStatus::tryFrom('no-such-value'))->toBeNull();
    });
    test('labels resolve in both locales: labels resolve in English', function (): void {
        app()->setLocale('en');

        expect(AuditStatus::PASS->label())->toBe('Pass');
        expect(AuditStatus::FAIL->label())->toBe('Fail');
        expect(AuditStatus::WARN->label())->toBe('Warn');
    });

    test('labels resolve in both locales: labels resolve in Indonesian', function (): void {
        app()->setLocale('id');

        expect(AuditStatus::PASS->label())->toBe('Lulus');
        expect(AuditStatus::FAIL->label())->toBe('Gagal');
        expect(AuditStatus::WARN->label())->toBe('Peringatan');
    });
    test('each status carries its fixed symbol', function (): void {
        expect(AuditStatus::PASS->symbol())->toBe('✓');
        expect(AuditStatus::FAIL->symbol())->toBe('✗');
        expect(AuditStatus::WARN->symbol())->toBe('⚠');
    });
});
