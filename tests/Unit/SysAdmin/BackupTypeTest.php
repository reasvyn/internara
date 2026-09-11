<?php

declare(strict_types=1);

use App\Modules\SysAdmin\Domain\Backups\Enums\BackupType;

describe('HBXCI: BackupType enum', function (): void {
    test('HBXCI-FR-BACK-004: cases carry the specified backing values', function (): void {
        expect(BackupType::DATABASE->value)->toBe('database');
        expect(BackupType::from('database'))->toBe(BackupType::DATABASE);
        expect(BackupType::STORAGE->value)->toBe('storage');
        expect(BackupType::from('storage'))->toBe(BackupType::STORAGE);
        expect(BackupType::BOTH->value)->toBe('both');
        expect(BackupType::from('both'))->toBe(BackupType::BOTH);
        expect(BackupType::cases())->toHaveCount(3);
        expect(BackupType::tryFrom('no-such-value'))->toBeNull();
    });
    test('HBXCI-FR-BACK-004: labels resolve in English', function (): void {
        app()->setLocale('en');

        expect(BackupType::DATABASE->label())->toBe('Database');
        expect(BackupType::STORAGE->label())->toBe('Storage');
        expect(BackupType::BOTH->label())->toBe('Full');
    });

    test('HBXCI-FR-BACK-004: labels resolve in Indonesian', function (): void {
        app()->setLocale('id');

        expect(BackupType::DATABASE->label())->toBe('Database');
        expect(BackupType::STORAGE->label())->toBe('Penyimpanan');
        expect(BackupType::BOTH->label())->toBe('Lengkap');
    });
});
