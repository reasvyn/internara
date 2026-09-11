<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(LazilyRefreshDatabase::class);

function runHealth(bool $json = false): array
{
    $exit = Artisan::call('system:health', $json ? ['--json' => true] : []);

    return [$exit, Artisan::output()];
}

describe('J68GZ and E1MSJ: system health command', function (): void {
    test('J68GZ-FR-SYS-032: system:health runs from the CLI and reports a summary', function (): void {
        [$exit, $output] = runHealth();

        expect($exit)->toBeIn([0, 1])
            ->and($output)->toContain(__('setup.system.health_header'));

        if ($exit === 0) {
            expect($output)->toContain(__('setup.system.health_passed'));
        } else {
            expect($output)->toContain(__('setup.system.health_failed'));
        }
    });

    test('J68GZ-FR-SYS-031: health output covers fifteen subsystem checks', function (): void {
        [$exit, $output] = runHealth(true);
        $rows = json_decode(trim($output), true);

        $labels = array_column($rows, 0);

        expect($exit)->toBe(0)
            ->and($rows)->toHaveCount(15)
            ->and($labels)->toContain(__('setup.system.environment'))
            ->and($labels)->toContain(__('setup.system.setup_status'))
            ->and($labels)->toContain(__('setup.system.php_version'))
            ->and($labels)->toContain(__('setup.system.database'))
            ->and($labels)->toContain(__('setup.system.cache'))
            ->and($labels)->toContain(__('setup.system.app_key'));

        foreach ($rows as $row) {
            expect($row[1])->toBeIn(['OK', 'WARN', 'FAIL']);
        }
    });

    test('E1MSJ-FR-MAINT-002: machine-readable output always exits successfully', function (): void {
        [$exit, $output] = runHealth(true);
        $rows = json_decode(trim($output), true);

        expect($exit)->toBe(0)
            ->and($rows)->toBeArray()
            ->and($rows)->toHaveCount(15);
    });

    test('E1MSJ-FR-MAINT-003: disk and queue pressure surface as report rows', function (): void {
        [, $output] = runHealth(true);
        $rows = json_decode(trim($output), true);

        $labels = array_column($rows, 0);

        expect($labels)->toContain(__('setup.system.disk_space'))
            ->and($labels)->toContain(__('setup.system.queue'));
    });
});
