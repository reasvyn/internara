<?php

declare(strict_types=1);

use App\SysAdmin\Backups\Actions\ReadBackupStatsAction;
use App\SysAdmin\Backups\Models\Backup;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

describe('ReadBackupStatsAction', function () {
    test('returns stats with backup data', function () {
        Backup::factory()->count(3)->create();
        Backup::factory()->failed()->count(1)->create();

        $stats = app(ReadBackupStatsAction::class)->execute();

        expect($stats)->toHaveKeys(['total', 'completed', 'failed', 'latest']);
        expect($stats['total'])->toBe(4);
        expect($stats['completed'])->toBe(3);
        expect($stats['failed'])->toBe(1);
    });

    test('returns latest completed backup', function () {
        $latest = Backup::factory()->create([
            'status' => 'completed',
            'created_at' => now()->subHour(),
        ]);
        Backup::factory()->count(2)->create([
            'status' => 'completed',
            'created_at' => now()->subDays(1),
        ]);

        $stats = app(ReadBackupStatsAction::class)->execute();

        expect($stats['latest']->id)->toBe($latest->id);
    });

    test('returns zero stats when no backups exist', function () {
        $stats = app(ReadBackupStatsAction::class)->execute();

        expect($stats['total'])->toBe(0);
        expect($stats['completed'])->toBe(0);
        expect($stats['failed'])->toBe(0);
        expect($stats['latest'])->toBeNull();
    });
});
