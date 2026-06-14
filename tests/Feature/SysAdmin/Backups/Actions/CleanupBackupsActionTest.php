<?php

declare(strict_types=1);

use App\SysAdmin\Backups\Actions\CleanupBackupsAction;
use App\SysAdmin\Backups\Models\Backup;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('deletes backups older than retention days', function () {
    Backup::factory()->create(['created_at' => now()->subDays(60)]);
    Backup::factory()->create(['created_at' => now()->subDays(45)]);
    Backup::factory()->create(['created_at' => now()->subDays(10)]);

    $deleted = app(CleanupBackupsAction::class)->execute(30);

    expect($deleted)->toBe(2);
    expect(Backup::count())->toBe(1);
});

test('does not delete backups within retention period', function () {
    Backup::factory()->count(3)->create(['created_at' => now()->subDays(5)]);

    $deleted = app(CleanupBackupsAction::class)->execute(30);

    expect($deleted)->toBe(0);
    expect(Backup::count())->toBe(3);
});

test('does not delete failed backups during cleanup', function () {
    Backup::factory()->failed()->create(['created_at' => now()->subDays(60)]);

    $deleted = app(CleanupBackupsAction::class)->execute(30);

    expect($deleted)->toBe(0);
});
