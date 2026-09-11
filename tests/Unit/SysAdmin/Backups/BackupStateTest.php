<?php

declare(strict_types=1);

use App\Modules\SysAdmin\Domain\Backups\Entities\BackupState;
use App\Modules\SysAdmin\Domain\Backups\Enums\BackupStatus;
use App\Modules\SysAdmin\Domain\Backups\Enums\BackupType;
use Illuminate\Database\Eloquent\Model;

final class BackupStateModelDouble extends Model
{
    protected $guarded = [];
}

describe('HBXCI: backup state', function (): void {
    test('HBXCI-FR-BACK-005: fromModel bridges the backup row without persisting', function (): void {
        $model = new BackupStateModelDouble([
            'status' => BackupStatus::COMPLETED->value,
            'type' => BackupType::DATABASE->value,
            'file_size' => 2048,
            'error_output' => null,
        ]);

        $state = BackupState::fromModel($model);

        expect($state->isCompleted())->toBeTrue();
        expect($state->isFailed())->toBeFalse();
        expect($state->isDeletable())->toBeTrue();
        expect($state->formattedSize())->toBe('2 KB');
        expect($state->type())->toBe(BackupType::DATABASE);
        expect($model->exists)->toBeFalse();
    });

    test('HBXCI-FR-BACK-005: fromModel bridges a failed run with its error output', function (): void {
        $model = new BackupStateModelDouble([
            'status' => BackupStatus::FAILED->value,
            'type' => BackupType::BOTH->value,
            'file_size' => 0,
            'error_output' => 'mysqldump: Got errno 28',
        ]);

        $state = BackupState::fromModel($model);

        expect($state->isFailed())->toBeTrue();
        expect($state->isCompleted())->toBeFalse();
        expect($state->isDeletable())->toBeTrue();
        expect($state->type())->toBe(BackupType::BOTH);
        expect($model->exists)->toBeFalse();
    });

    test('HBXCI-FR-BACK-005: isDeletable allows only terminal runs', function (): void {
        $base = ['type' => BackupType::STORAGE->value, 'fileSize' => 100, 'errorOutput' => null];

        expect(BackupState::fromArray([...$base, 'status' => BackupStatus::COMPLETED->value])->isDeletable())->toBeTrue();
        expect(BackupState::fromArray([...$base, 'status' => BackupStatus::FAILED->value])->isDeletable())->toBeTrue();
        expect(BackupState::fromArray([...$base, 'status' => BackupStatus::RUNNING->value])->isDeletable())->toBeFalse();
        expect(BackupState::fromArray([...$base, 'status' => BackupStatus::PENDING->value])->isDeletable())->toBeFalse();
    });

    test('HBXCI-FR-BACK-005: formattedSize renders human units', function (): void {
        $base = ['status' => BackupStatus::COMPLETED->value, 'type' => BackupType::DATABASE->value, 'errorOutput' => null];

        expect(BackupState::fromArray([...$base, 'fileSize' => 0])->formattedSize())->toBe('0 B');
        expect(BackupState::fromArray([...$base, 'fileSize' => 512])->formattedSize())->toBe('512 B');
        expect(BackupState::fromArray([...$base, 'fileSize' => 1536])->formattedSize())->toBe('1.5 KB');
        expect(BackupState::fromArray([...$base, 'fileSize' => 5 * 1024 * 1024])->formattedSize())->toBe('5 MB');
    });

    test('HBXCI-FR-BACK-004: type resolves the stored backup kind', function (): void {
        $base = ['status' => BackupStatus::COMPLETED->value, 'fileSize' => 10, 'errorOutput' => null];

        expect(BackupState::fromArray([...$base, 'type' => BackupType::STORAGE->value])->type())->toBe(BackupType::STORAGE);
    });

    test('HBXCI-FR-BACK-005: fromArray rejects a missing status', function (): void {
        expect(fn (): BackupState => BackupState::fromArray([
            'type' => BackupType::DATABASE->value, 'fileSize' => 0, 'errorOutput' => null,
        ]))->toThrow(InvalidArgumentException::class, 'status');
    });

    test('HBXCI-FR-BACK-005: toArray, equals, and with round-trip by value', function (): void {
        $state = BackupState::fromArray([
            'status' => BackupStatus::RUNNING->value,
            'type' => BackupType::DATABASE->value,
            'fileSize' => 1024,
            'errorOutput' => null,
        ]);

        expect($state->toArray())->toBe([
            'status' => BackupStatus::RUNNING->value,
            'type' => BackupType::DATABASE->value,
            'fileSize' => 1024,
            'errorOutput' => null,
        ]);
        expect($state->equals(BackupState::fromArray([
            'status' => BackupStatus::RUNNING->value,
            'type' => BackupType::DATABASE->value,
            'fileSize' => 1024,
            'errorOutput' => null,
        ])))->toBeTrue();

        $completed = $state->with('status', BackupStatus::COMPLETED->value);

        expect($completed->isDeletable())->toBeTrue();
        expect($state->isDeletable())->toBeFalse();
    });
});
