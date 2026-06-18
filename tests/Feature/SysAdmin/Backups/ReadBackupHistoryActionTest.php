<?php

declare(strict_types=1);

use App\SysAdmin\Backups\Actions\ReadBackupHistoryAction;
use App\SysAdmin\Backups\Enums\BackupStatus;
use App\SysAdmin\Backups\Models\Backup;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

describe('ReadBackupHistoryAction', function () {
    test('returns paginated backup history', function () {
        Backup::factory()->count(5)->create();

        $result = app(ReadBackupHistoryAction::class)->execute();

        expect($result)->toBeInstanceOf(LengthAwarePaginator::class);
        expect($result->total())->toBe(5);
    });

    test('filters by type', function () {
        Backup::factory()->database()->count(3)->create();
        Backup::factory()->storage()->count(2)->create();

        $result = app(ReadBackupHistoryAction::class)->execute(type: 'database');

        expect($result->total())->toBe(3);
    });

    test('filters by status', function () {
        Backup::factory()->count(3)->create();
        Backup::factory()->failed()->count(2)->create();

        $result = app(ReadBackupHistoryAction::class)->execute(status: BackupStatus::COMPLETED->value);

        expect($result->total())->toBe(3);
    });

    test('respects per page parameter', function () {
        Backup::factory()->count(10)->create();

        $result = app(ReadBackupHistoryAction::class)->execute(perPage: 5);

        expect($result->perPage())->toBe(5);
    });

    test('returns empty paginator when no backups exist', function () {
        $result = app(ReadBackupHistoryAction::class)->execute();

        expect($result->total())->toBe(0);
    });
});
