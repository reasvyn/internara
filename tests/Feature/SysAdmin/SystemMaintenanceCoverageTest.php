<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

describe('E1MSJ system maintenance', function (): void {
    test('E1MSJ-FR-MAINT-004 E1MSJ-FR-MAINT-005 E1MSJ-NFR-MAINT-001 E1MSJ-NFR-MAINT-002 E1MSJ-NFR-MAINT-003 E1MSJ-DD-MAINT-001: cleanup is confirmable, resilient, logged, and masked', function (): void {
        $source = file_get_contents(base_path('app/Modules/SysAdmin/Domain/Observability/Console/Commands/SystemCleanupCommand.php'));

        expect($source)->toContain('{--force')
            ->and($source)->toContain('{--log-retention=30')
            ->and($source)->toContain("'auth:clear-resets'")
            ->and($source)->toContain("'media-library:clean'")
            ->and($source)->toContain('catch (\\Throwable $e)')
            ->and($source)->toContain('withPiiMasking()')
            ->and($source)->toContain("event('cleanup.completed')");
    });

    test('E1MSJ-FR-MAINT-006 E1MSJ-UC-MAINT-003: notification pruning enforces a safe retention floor', function (): void {
        $source = file_get_contents(base_path('app/Modules/SysAdmin/Console/Commands/PruneNotificationsCommand.php'));

        expect($source)->toContain('{--days=30')
            ->and($source)->toContain('if ($days < 1)')
            ->and($source)->toContain("where('is_read', true)")
            ->and($source)->toContain("where('created_at', '<', \$cutoff)")
            ->and($source)->toContain('prune_notifications.completed');
    });

    test('E1MSJ-FR-MAINT-007 E1MSJ-NFR-MAINT-005 E1MSJ-DD-MAINT-002: cache warming runs every registered subsystem through translated tasks', function (): void {
        $source = file_get_contents(base_path('app/Modules/SysAdmin/Domain/Observability/Console/Commands/SystemCacheWarmCommand.php'));

        expect($source)->toContain('warmSettings()')
            ->and($source)->toContain('warmBrand()')
            ->and($source)->toContain('warmConfig()')
            ->and($source)->toContain('warmViews()')
            ->and($source)->toContain('warmEvents()')
            ->and($source)->toContain('setup.system.cache_warm_completed')
            ->and($source)->toContain("event('cache.warm.completed')");
    });

    test('E1MSJ-FR-MAINT-008 E1MSJ-FR-MAINT-009 E1MSJ-FR-MAINT-010 E1MSJ-NFR-MAINT-004 E1MSJ-UC-MAINT-004 E1MSJ-UC-MAINT-005 E1MSJ-DD-MAINT-003: account retirement has guarded sync and queued paths', function (): void {
        $inactivate = file_get_contents(base_path('app/Modules/User/Domain/UserManagement/Console/Commands/AutoInactivateAccounts.php'));
        $archive = file_get_contents(base_path('app/Modules/User/Domain/UserManagement/Actions/ArchiveStudentAccountsAction.php'));
        $dispatch = file_get_contents(base_path('app/Modules/User/Domain/UserManagement/Actions/DispatchArchiveStudentAccountsAction.php'));
        $job = file_get_contents(base_path('app/Modules/User/Jobs/ArchiveStudentAccountsJob.php'));

        expect($inactivate)->toContain('inactivate')
            ->and($archive)->toContain('chunk(100')
            ->and($archive)->toContain('ARCHIVED')
            ->and($dispatch)->toContain('ArchiveStudentAccountsJob::dispatch')
            ->and($job)->toContain('ShouldQueue')
            ->and($job)->toContain('tries');
    });
});
