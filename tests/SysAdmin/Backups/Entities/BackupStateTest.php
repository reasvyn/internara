<?php

declare(strict_types=1);

use App\SysAdmin\Backups\Entities\BackupState;
use App\SysAdmin\Backups\Enums\BackupType;

test('backup state detects completed', function () {
    $completed = new BackupState('completed', 'database', 1024, null);
    expect($completed->isCompleted())->toBeTrue();

    $failed = new BackupState('failed', 'database', 0, 'error');
    expect($failed->isCompleted())->toBeFalse();
});

test('backup state detects failed', function () {
    $failed = new BackupState('failed', 'database', 0, 'error');
    expect($failed->isFailed())->toBeTrue();

    $completed = new BackupState('completed', 'database', 1024, null);
    expect($completed->isFailed())->toBeFalse();
});

test('backup state determines deletable status', function () {
    $completed = new BackupState('completed', 'database', 1024, null);
    expect($completed->isDeletable())->toBeTrue();

    $failed = new BackupState('failed', 'database', 0, 'error');
    expect($failed->isDeletable())->toBeTrue();

    $pending = new BackupState('pending', 'database', 0, null);
    expect($pending->isDeletable())->toBeFalse();

    $running = new BackupState('running', 'storage', 0, null);
    expect($running->isDeletable())->toBeFalse();
});

test('backup state formats file size', function () {
    expect((new BackupState('completed', 'database', 0, null))->formattedSize())->toBe('0 B');
    expect((new BackupState('completed', 'database', 500, null))->formattedSize())->toBe('500 B');
    expect((new BackupState('completed', 'database', 1536, null))->formattedSize())->toBe('1.5 KB');
    expect((new BackupState('completed', 'database', 2097152, null))->formattedSize())->toBe('2 MB');
    expect((new BackupState('completed', 'database', 3221225472, null))->formattedSize())->toBe('3 GB');
});

test('backup state returns backup type', function () {
    $state = new BackupState('completed', 'database', 0, null);
    expect($state->type())->toBe(BackupType::DATABASE);

    $state2 = new BackupState('pending', 'both', 0, null);
    expect($state2->type())->toBe(BackupType::BOTH);
});
