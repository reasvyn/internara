<?php

declare(strict_types=1);

namespace Tests\Feature\Setup\Console\Commands;

use App\Setup\Models\Setup;
use App\Setup\Support\SystemProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

beforeEach(function () {
    Setup::truncate();
    Cache::flush();
});

test('fails when system is already installed', function () {
    Setup::factory()->installed()->create();

    $this->artisan('setup:install')
        ->assertExitCode(1)
        ->expectsOutputToContain(__('setup.cli.already_installed'));
});

test('fails when system is installed and force is used in restricted environment', function () {
    Setup::factory()->installed()->create();
    config(['setup.force_allowed_environments' => ['local', 'dev']]);

    $this->artisan('setup:install', ['--force' => true])
        ->assertExitCode(1)
        ->expectsOutputToContain(__('setup.cli.force_restricted'));
});

test('fails when audit does not pass', function () {
    config()->set('setup.requirements.php_version', '999.0.0');

    $this->artisan('setup:install')
        ->assertExitCode(1)
        ->expectsOutputToContain(__('setup.cli.audit_failed'));
});

test('check-only runs audit and succeeds without provisioning', function () {
    $this->artisan('setup:install', ['--check-only' => true])
        ->assertExitCode(0)
        ->expectsOutputToContain(__('setup.cli.check_only_complete'));
});

test('aborts when confirmation is declined', function () {
    $this->artisan('setup:install')
        ->expectsConfirmation(__('setup.cli.proceed_confirm'), 'no')
        ->assertExitCode(1);
});

test('force reinstall succeeds in testing environment and outputs token', function () {
    Setup::factory()->installed()->create();

    $this->partialMock(SystemProvisioner::class, function ($mock) {
        $mock->shouldReceive('getTasks')->andReturn([]);
    });

    $this->artisan('setup:install', ['--force' => true])
        ->assertExitCode(0)
        ->expectsOutputToContain(__('setup.cli.quick_access'));
});

test('installs successfully with confirmation and outputs setup token', function () {
    $this->partialMock(SystemProvisioner::class, function ($mock) {
        $mock->shouldReceive('getTasks')->andReturn([]);
    });

    $this->artisan('setup:install')
        ->expectsConfirmation(__('setup.cli.proceed_confirm'), 'yes')
        ->assertExitCode(0)
        ->expectsOutputToContain(__('setup.cli.installation_completed'));
});
