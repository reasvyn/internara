<?php

declare(strict_types=1);

namespace Tests\Feature\Setup\Console\Commands;

use App\Setup\Models\Setup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

beforeEach(function () {
    Cache::flush();
});

test('fails when setups table does not exist', function () {
    Schema::dropIfExists('setups');

    $this->artisan('setup:reset-token')
        ->assertExitCode(1)
        ->expectsOutputToContain(__('setup.reset_token.table_missing'));
});

test('fails when system is already installed', function () {
    Setup::factory()->installed()->create();

    $this->artisan('setup:reset-token')
        ->assertExitCode(1)
        ->expectsOutputToContain(__('setup.reset_token.protected'));
});

test('generates new token and shows quick access link when not installed', function () {
    Setup::truncate();
    Setup::factory()->create();

    $this->artisan('setup:reset-token')
        ->assertExitCode(0)
        ->expectsOutputToContain(__('setup.cli.quick_access'));
});

test('increments token version after reset', function () {
    Setup::truncate();
    Setup::factory()->create();

    $this->artisan('setup:reset-token')->assertExitCode(0);

    $setup = Setup::first();
    expect($setup->token_version)->toBe(1);
});
