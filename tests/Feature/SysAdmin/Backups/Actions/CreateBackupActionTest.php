<?php

declare(strict_types=1);

use App\Core\Exceptions\RejectedException;
use App\SysAdmin\Backups\Actions\CreateBackupAction;
use App\SysAdmin\Backups\Enums\BackupType;
use App\SysAdmin\Backups\Models\Backup;
use App\SysAdmin\Backups\Support\BackupRunner;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $runner = Mockery::mock(BackupRunner::class);
    $runner->shouldReceive('runDatabaseDump')->andReturn('backup/test_db.sql.gz');
    $runner->shouldReceive('runStorageDump')->andReturn('backup/test_storage.tar.gz');
    $runner->shouldReceive('runCombinedDump')->andReturn('backup/test_both.tar.gz');
    $runner->shouldReceive('fileSize')->andReturn(1024);

    $this->app->instance(BackupRunner::class, $runner);
});

test('creates a database backup record', function () {
    $user = User::factory()->create();

    $backup = app(CreateBackupAction::class)->execute(BackupType::DATABASE, $user);

    expect($backup)->toBeInstanceOf(Backup::class);
    expect($backup->type)->toBe(BackupType::DATABASE->value);
    expect($backup->created_by)->toBe($user->id);
});

test('creates a storage backup record', function () {
    $backup = app(CreateBackupAction::class)->execute(BackupType::STORAGE);

    expect($backup)->toBeInstanceOf(Backup::class);
    expect($backup->type)->toBe(BackupType::STORAGE->value);
});

test('creates a full backup record', function () {
    $backup = app(CreateBackupAction::class)->execute(BackupType::BOTH);

    expect($backup)->toBeInstanceOf(Backup::class);
    expect($backup->type)->toBe(BackupType::BOTH->value);
});

test('backup transitions from running to completed', function () {
    $backup = app(CreateBackupAction::class)->execute(BackupType::DATABASE);

    expect($backup->status)->toBe('completed');
    expect($backup->file_size)->toBe(1024);
});

test('throws exception when backup runner fails', function () {
    $runner = Mockery::mock(BackupRunner::class);
    $runner->shouldReceive('runDatabaseDump')->andThrow(new RuntimeException('Backup failed'));
    $runner->shouldReceive('fileSize')->andReturn(0);

    $this->app->instance(BackupRunner::class, $runner);

    app(CreateBackupAction::class)->execute(BackupType::DATABASE);
})->throws(RejectedException::class);
