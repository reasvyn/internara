<?php

declare(strict_types=1);

namespace Tests\Feature\Setup\Installation\Console\Commands;

use Tests\Support\WithSettingsSeed;
use App\Settings\Services\Settings;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

uses(LazilyRefreshDatabase::class);
uses(WithSettingsSeed::class);

beforeEach(function () {
    $this->seedSettings([
        'setup.is_installed' => ['value' => false, 'group' => 'setup', 'type' => 'boolean'],
    ]);
    Cache::flush();
});

test('fails when settings table does not exist', function () {
    Schema::dropIfExists('settings');

    $this->artisan('setup:reset-token')
        ->assertExitCode(1)
        ->expectsOutputToContain(__('setup.reset_token.table_missing'));
});

test('fails when system is already installed', function () {
    $this->seedSettings([
        'setup.is_installed' => ['value' => true, 'group' => 'setup', 'type' => 'boolean'],
    ]);
    Cache::flush();
    // Re-set in setting after flush
    $this->seedSettings([
        'setup.is_installed' => ['value' => true, 'group' => 'setup', 'type' => 'boolean'],
    ]);

    $this->artisan('setup:reset-token')
        ->assertExitCode(1)
        ->expectsOutputToContain(__('setup.reset_token.protected'));
});

test('generates new token and shows quick access link when not installed', function () {
    $this->artisan('setup:reset-token')
        ->assertExitCode(0)
        ->expectsOutputToContain(__('setup.cli.quick_access'));
});

test('increments token version after reset', function () {
    $this->artisan('setup:reset-token')->assertExitCode(0);

    expect(Settings::get('setup.token_version'))->toBe(1);
});
