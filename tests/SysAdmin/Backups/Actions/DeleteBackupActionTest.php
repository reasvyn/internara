<?php

declare(strict_types=1);

use App\Core\Exceptions\RejectedException;
use App\SysAdmin\Backups\Actions\DeleteBackupAction;
use App\SysAdmin\Backups\Models\Backup;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('deletes a completed backup', function () {
    $backup = Backup::factory()->create();

    expect(Backup::count())->toBe(1);

    app(DeleteBackupAction::class)->execute($backup);

    expect(Backup::count())->toBe(0);
});

test('throws exception when deleting a running backup', function () {
    $backup = Backup::factory()->pending()->create();

    app(DeleteBackupAction::class)->execute($backup);
})->throws(RejectedException::class);
