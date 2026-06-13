<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    Config::set('setup.requirements.php_version', PHP_VERSION);
});

test('runs health check and returns success', function () {
    $this->artisan('system:health')
        ->assertExitCode(0)
        ->expectsOutputToContain(__('setup.system.health_passed'));
});

test('returns json output when --json flag is used', function () {
    $this->artisan('system:health', ['--json' => true])
        ->assertExitCode(0);
});

test('fails when required extension is missing', function () {
    Config::set('setup.requirements.extensions', ['some_missing_ext_xyz']);

    $this->artisan('system:health')
        ->assertExitCode(1)
        ->expectsOutputToContain(__('setup.system.health_failed'));
});

test('fails when php version is below requirement', function () {
    Config::set('setup.requirements.php_version', '999.0.0');

    $this->artisan('system:health')
        ->assertExitCode(1)
        ->expectsOutputToContain(__('setup.system.health_failed'));
});

test('warns when .env file is missing', function () {
    File::shouldReceive('exists')
        ->with(base_path('.env'))
        ->andReturn(false);

    $this->artisan('system:health')
        ->assertExitCode(1)
        ->expectsOutputToContain(__('setup.system.health_failed'));
});
