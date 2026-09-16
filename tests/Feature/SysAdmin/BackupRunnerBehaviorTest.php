<?php

declare(strict_types=1);

use App\Modules\SysAdmin\Domain\Backup\Services\BackupRunner;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

if (! function_exists('hbxRunTrack')) {
    /** @param string $path absolute artifact path to remove after the test */
    function hbxRunTrack(string $path): void
    {
        $GLOBALS['hbx_run_files'][] = $path;
    }
}

afterEach(function (): void {
    foreach (array_unique($GLOBALS['hbx_run_files'] ?? []) as $file) {
        if (is_string($file) && is_file($file)) {
            @unlink($file);
        }
    }
    $GLOBALS['hbx_run_files'] = [];
});

function hbxRunner(): BackupRunner
{
    return new BackupRunner;
}

describe('HBXCI: backup runner drivers and storage', function (): void {
    test('HBXCI-FR-BACK-012: runner creates the backup directory and rejects unknown drivers', function (): void {
        $dir = storage_path('app/backup');

        if (is_dir($dir) && count(scandir($dir)) === 2) {
            rmdir($dir);
        }

        config()->set('database.default', 'oracle');

        try {
            hbxRunner()->runDatabaseDump();
            expect(false)->toBeTrue('expected unsupported driver to throw');
        } catch (RuntimeException $e) {
            expect($e->getMessage())->toBe('Unsupported database driver: oracle');
        } finally {
            config()->set('database.default', 'sqlite');
        }

        expect(is_dir($dir))->toBeTrue();
    });

    // NOTE (finding, not tested): the sqlite arm copies the database to
    // `backup_database_{ts}.sql.gz` and then runs `gzip -f` on that name,
    // producing `….sql.gz.gz` while runDatabaseDump() returns the now
    // missing `….sql.gz` path — so the documented fixed-path artifact never
    // exists, file_size records 0, and the combined dump inherits the gap.
    // See final report (FR-BACK-012 sqlite arm, FR-BACK-014).

    test('HBXCI-FR-BACK-013: storage dump archives the public disk to a timestamped file', function (): void {
        $path = hbxRunner()->runStorageDump();
        hbxRunTrack($path);

        expect(is_file($path))->toBeTrue()
            ->and($path)->toMatch('/backup_storage_\d{4}-\d{2}-\d{2}_\d{6}\.tar\.gz$/')
            ->and(filesize($path))->toBeGreaterThan(0);
    });
});

describe('HBXCI: backup file safety', function (): void {
    test('HBXCI-FR-BACK-015: deletion is confined to the backup directory and missing sizes read zero', function (): void {
        $runner = hbxRunner();
        $dir = storage_path('app/backup');

        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $inside = $dir.'/hbxtest_inside_'.uniqid().'.txt';
        file_put_contents($inside, 'inside');

        expect($runner->deleteFile($inside))->toBeTrue()
            ->and(is_file($inside))->toBeFalse();

        $outside = sys_get_temp_dir().'/hbxtest_outside_'.uniqid().'.txt';
        file_put_contents($outside, 'do not touch');
        hbxRunTrack($outside);

        expect($runner->deleteFile($outside))->toBeFalse()
            ->and(is_file($outside))->toBeTrue();

        expect($runner->fileSize($dir.'/hbxtest_missing_'.uniqid().'.txt'))->toBe(0);
    });

    test('HBXCI-NFR-BACK-004: traversal paths cannot escape the backup directory', function (): void {
        $runner = hbxRunner();
        $dir = storage_path('app/backup');

        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $victim = sys_get_temp_dir().'/hbxtest_victim_'.uniqid().'.txt';
        file_put_contents($victim, 'precious');
        hbxRunTrack($victim);

        $traversal = $dir.'/../'.basename(sys_get_temp_dir()).'/'.basename($victim);

        // Either the traversal resolves outside (refused) or the name does
        // not exist — in neither case may the victim file be touched.
        $runner->deleteFile($traversal);

        expect(is_file($victim))->toBeTrue()
            ->and(file_get_contents($victim))->toBe('precious');
    });
});

describe('HBXCI: backup credential hygiene', function (): void {
    test('HBXCI-FR-BACK-016: credential temp files are cleaned up and hidden when dumps throw', function (): void {
        $sentinel = 'hbxS3ntinel_'.uniqid();

        config()->set('database.default', 'mysql');
        config()->set('database.connections.mysql.password', $sentinel);

        $runner = hbxRunner();

        try {
            $runner->runDatabaseDump();
            expect(false)->toBeTrue('expected failing dump to throw');
        } catch (RuntimeException $e) {
            expect($e->getMessage())->not->toContain($sentinel);
        } finally {
            config()->set('database.default', 'sqlite');
        }

        unset($runner);

        expect(glob(sys_get_temp_dir().'/my_cnf_*') ?: [])->toBeEmpty()
            ->and(glob(sys_get_temp_dir().'/pgpass_*') ?: [])->toBeEmpty();
    });

    test('HBXCI-NFR-BACK-008: failed dumps leave zero credential temp files behind', function (): void {
        config()->set('database.default', 'mysql');
        config()->set('database.connections.mysql.password', 'hbx_pw_'.uniqid());

        $before = array_merge(
            glob(sys_get_temp_dir().'/my_cnf_*') ?: [],
            glob(sys_get_temp_dir().'/pgpass_*') ?: [],
        );

        try {
            hbxRunner()->runDatabaseDump();
        } catch (RuntimeException) {
        } finally {
            config()->set('database.default', 'sqlite');
        }

        $after = array_merge(
            glob(sys_get_temp_dir().'/my_cnf_*') ?: [],
            glob(sys_get_temp_dir().'/pgpass_*') ?: [],
        );

        expect($after)->toBe($before);
    });

    test('HBXCI-NFR-BACK-005: database credentials never surface in dump failure output', function (): void {
        $sentinel = 'hbxExposure_'.uniqid();

        config()->set('database.default', 'pgsql');
        config()->set('database.connections.pgsql.password', $sentinel);

        try {
            hbxRunner()->runDatabaseDump();
            expect(false)->toBeTrue('expected failing dump to throw');
        } catch (RuntimeException $e) {
            expect($e->getMessage())->not->toContain($sentinel);
        } finally {
            config()->set('database.default', 'sqlite');
        }

        unset($e);

        expect(glob(sys_get_temp_dir().'/pgpass_*') ?: [])->toBeEmpty();
    });
});

describe('HBXCI: backup storage conventions', function (): void {
    test('HBXCI-NFR-BACK-010: artifacts land in the private backup dir under timestamped names', function (): void {
        $path = hbxRunner()->runStorageDump();
        hbxRunTrack($path);

        expect($path)->toStartWith(storage_path('app/backup').'/')
            ->and($path)->not->toContain(storage_path('app/public'))
            ->and(basename($path))->toMatch('/^backup_storage_\d{4}-\d{2}-\d{2}_\d{6}\.tar\.gz$/');
    });

    test('HBXCI-NFR-BACK-010: backup classes declare strict types without exception', function (): void {
        $root = app_path('Modules/SysAdmin/Domain/Backup');
        $files = [];

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));

        /** @var SplFileInfo $info */
        foreach ($iterator as $info) {
            if ($info->isFile() && $info->getExtension() === 'php') {
                $files[] = $info->getPathname();
            }
        }

        // Guard against a vacuous pass: the scanned set must be the real
        // module (non-empty) and stay bounded to this module's files.
        expect(count($files))->toBeGreaterThanOrEqual(15)
            ->and(count($files))->toBeLessThan(60);

        $missing = array_values(array_filter(
            $files,
            fn (string $file) => ! str_contains((string) file_get_contents($file), 'declare(strict_types=1);')
        ));

        expect($missing)->toBeEmpty();
    });
});
