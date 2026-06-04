<?php

declare(strict_types=1);

namespace Tests\Feature\SysAdmin\Console;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

test('system:health outputs plain text table and passes on good health', function () {
    $exitCode = Artisan::call('system:health');
    $output = Artisan::output();

    expect($exitCode)->toBe(0);
    expect($output)->toContain(__('setup.system.service'));
    expect($output)->toContain(__('setup.system.status'));
    expect($output)->toContain(__('setup.system.details'));
    expect($output)->toContain(__('setup.system.health_passed'));
});

test('system:health outputs valid JSON format when --json flag is passed', function () {
    $exitCode = Artisan::call('system:health', ['--json' => true]);
    $output = Artisan::output();

    expect($exitCode)->toBe(0);
    expect($output)->toContain(__('setup.system.php_version'));
    expect($output)->toContain(__('setup.system.database'));
    expect(json_decode($output))->not->toBeNull();
});

test('system:health reports FAIL when database check throws exception', function () {
    DB::shouldReceive('connection')
        ->andThrow(new \RuntimeException('Connection failed'));

    $exitCode = Artisan::call('system:health');
    $output = Artisan::output();

    expect($exitCode)->toBe(1);
    expect($output)->toContain(__('setup.system.health_failed'));
});
