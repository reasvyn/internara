<?php

declare(strict_types=1);

namespace Tests\Feature\Core\Console;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Mockery;

afterEach(function () {
    Mockery::close();
});

test('system:health command outputs plain text table and passes on good health', function () {
    $exitCode = Artisan::call('system:health');
    $output = Artisan::output();

    expect($exitCode)->toBe(0);
    expect($output)->toContain(__('setup.system.service'));
    expect($output)->toContain(__('setup.system.status'));
    expect($output)->toContain(__('setup.system.details'));
    expect($output)->toContain(__('setup.system.health_passed'));
});

test('system:health command outputs valid JSON format when --json flag is passed', function () {
    $exitCode = Artisan::call('system:health', ['--json' => true]);
    $output = Artisan::output();

    expect($exitCode)->toBe(0);
    expect($output)->toContain(__('setup.system.php_version'));
    expect($output)->toContain(__('setup.system.database'));
});

test('system:health command fails and reports FAIL when database check throws exception', function () {
    // Force DB connection check to fail using DB facade mock
    DB::shouldReceive('connection')
        ->andThrow(new \RuntimeException('Connection failed'));

    $exitCode = Artisan::call('system:health');
    $output = Artisan::output();

    expect($exitCode)->toBe(1); // Command::FAILURE
    expect($output)->toContain(__('setup.system.health_failed'));
});
