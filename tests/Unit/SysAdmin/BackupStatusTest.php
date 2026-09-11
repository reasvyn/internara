<?php

declare(strict_types=1);

use App\Modules\Assignment\Enums\AssignmentStatus;
use App\Modules\SysAdmin\Domain\Backups\Enums\BackupStatus;

describe('HBXCI: BackupStatus enum', function (): void {
    test('HBXCI-FR-BACK-003: cases carry the specified backing values', function (): void {
        expect(BackupStatus::PENDING->value)->toBe('pending');
        expect(BackupStatus::from('pending'))->toBe(BackupStatus::PENDING);
        expect(BackupStatus::RUNNING->value)->toBe('running');
        expect(BackupStatus::from('running'))->toBe(BackupStatus::RUNNING);
        expect(BackupStatus::COMPLETED->value)->toBe('completed');
        expect(BackupStatus::from('completed'))->toBe(BackupStatus::COMPLETED);
        expect(BackupStatus::FAILED->value)->toBe('failed');
        expect(BackupStatus::from('failed'))->toBe(BackupStatus::FAILED);
        expect(BackupStatus::cases())->toHaveCount(4);
        expect(BackupStatus::tryFrom('no-such-value'))->toBeNull();
    });
    test('HBXCI-FR-BACK-003: labels resolve in English', function (): void {
        app()->setLocale('en');

        expect(BackupStatus::PENDING->label())->toBe('Pending');
        expect(BackupStatus::RUNNING->label())->toBe('Running');
        expect(BackupStatus::COMPLETED->label())->toBe('Completed');
        expect(BackupStatus::FAILED->label())->toBe('Failed');
    });

    test('HBXCI-FR-BACK-003: labels resolve in Indonesian', function (): void {
        app()->setLocale('id');

        expect(BackupStatus::PENDING->label())->toBe('Menunggu');
        expect(BackupStatus::RUNNING->label())->toBe('Berjalan');
        expect(BackupStatus::COMPLETED->label())->toBe('Selesai');
        expect(BackupStatus::FAILED->label())->toBe('Gagal');
    });
    test('HBXCI-FR-BACK-003: completed and failed are terminal, and finished mirrors terminal', function (): void {
        expect(BackupStatus::COMPLETED->isTerminal())->toBeTrue();
        expect(BackupStatus::FAILED->isTerminal())->toBeTrue();
        expect(BackupStatus::PENDING->isTerminal())->toBeFalse();
        expect(BackupStatus::RUNNING->isTerminal())->toBeFalse();
        expect(BackupStatus::COMPLETED->isFinished())->toBeTrue();
        expect(BackupStatus::FAILED->isFinished())->toBeTrue();
        expect(BackupStatus::PENDING->isFinished())->toBeFalse();
        expect(BackupStatus::RUNNING->isFinished())->toBeFalse();
    });

    test('HBXCI-FR-BACK-003: validTransitions pins the lifecycle map', function (): void {
        expect(BackupStatus::PENDING->validTransitions())->toBe([BackupStatus::RUNNING, BackupStatus::FAILED]);
        expect(BackupStatus::RUNNING->validTransitions())->toBe([BackupStatus::COMPLETED, BackupStatus::FAILED]);
        expect(BackupStatus::COMPLETED->validTransitions())->toBe([]);
        expect(BackupStatus::FAILED->validTransitions())->toBe([]);
    });

    test('HBXCI-FR-BACK-003: canTransitionTo follows the map and rejects foreign enums', function (): void {
        expect(BackupStatus::PENDING->canTransitionTo(BackupStatus::RUNNING))->toBeTrue();
        expect(BackupStatus::PENDING->canTransitionTo(BackupStatus::FAILED))->toBeTrue();
        expect(BackupStatus::RUNNING->canTransitionTo(BackupStatus::COMPLETED))->toBeTrue();
        expect(BackupStatus::PENDING->canTransitionTo(BackupStatus::COMPLETED))->toBeFalse();
        expect(BackupStatus::COMPLETED->canTransitionTo(BackupStatus::FAILED))->toBeFalse();
        expect(BackupStatus::PENDING->canTransitionTo(AssignmentStatus::DRAFT))->toBeFalse();
    });
});
