<?php

declare(strict_types=1);

use App\SysAdmin\Backups\Events\BackupCompleted;
use App\SysAdmin\Backups\Events\BackupFailed;
use App\SysAdmin\Backups\Models\Backup;

function makeBackup(string $id): Backup
{
    $model = new class extends Backup {};
    $model->forceFill(['id' => $id]);

    return $model;
}

test('backup completed event name and payload', function () {
    $event = new BackupCompleted(makeBackup('b-1'));

    expect($event->backup->id)->toBe('b-1');
    expect($event->eventName())->toBe('backup.completed');
    expect($event->toPayload())->toHaveKey('backup_id');
});

test('backup failed event name and payload', function () {
    $event = new BackupFailed(makeBackup('b-2'));

    expect($event->backup->id)->toBe('b-2');
    expect($event->eventName())->toBe('backup.failed');
    expect($event->toPayload())->toHaveKey('backup_id');
});
