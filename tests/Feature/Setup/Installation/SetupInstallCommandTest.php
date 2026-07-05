<?php

declare(strict_types=1);

namespace Tests\Feature\Setup\Installation\Console\Commands;

use Tests\Support\WithSettingsSeed;
use App\Setup\Installation\Services\SystemProvisioner;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(LazilyRefreshDatabase::class);
uses(WithSettingsSeed::class);

beforeEach(function () {
    $this->seedSettings([
        'setup.is_installed' => ['value' => false, 'group' => 'setup', 'type' => 'boolean'],
        'setup.install_token' => ['value' => null, 'group' => 'setup', 'type' => 'string'],
        'setup.token_expires_at' => ['value' => null, 'group' => 'setup', 'type' => 'datetime'],
    ]);
    Cache::flush();
});

test('fails when system is already installed', function () {
    $this->seedSettings([
        'setup.is_installed' => ['value' => true, 'group' => 'setup', 'type' => 'boolean'],
    ]);
    Cache::flush();

    $this->artisan('setup:install')
        ->assertExitCode(1)
        ->expectsOutputToContain(__('setup.cli.already_installed'));
});

test('fails when system is installed and force is used in restricted environment', function () {
    $this->seedSettings([
        'setup.is_installed' => ['value' => true, 'group' => 'setup', 'type' => 'boolean'],
    ]);
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
    $this->seedSettings([
        'setup.is_installed' => ['value' => true, 'group' => 'setup', 'type' => 'boolean'],
    ]);

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
