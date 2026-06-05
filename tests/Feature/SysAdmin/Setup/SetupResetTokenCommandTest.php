<?php

declare(strict_types=1);

namespace Tests\Feature\SysAdmin\Setup;

use App\SysAdmin\Setup\Models\Setup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

beforeEach(function () {
    Setup::factory()->create(['is_installed' => true]);
});

test('setup:reset-token fails if setups table does not exist', function () {
    Schema::dropIfExists('setups');

    $this->artisan('setup:reset-token')
        ->expectsOutputToContain(__('setup.reset_token.table_missing'))
        ->assertFailed();
});

test('setup:reset-token blocks execution if system is already installed', function () {
    $this->artisan('setup:reset-token')
        ->expectsOutputToContain(__('setup.reset_token.protected'))
        ->assertFailed();
});

test('setup:reset-token generates new token when system not installed', function () {
    Setup::query()->update(['is_installed' => false]);

    $this->artisan('setup:reset-token')
        ->expectsOutputToContain(__('setup.reset_token.new_token_generated'))
        ->assertSuccessful();

    $setup = Setup::first();
    expect($setup->setup_token)->not->toBeNull();
});

test('setup:reset-token overwrites existing token', function () {
    Setup::query()->update(['is_installed' => false]);

    $oldToken = Setup::first()->setup_token;

    $this->artisan('setup:reset-token')
        ->assertSuccessful();

    $setup = Setup::first();
    expect($setup->setup_token)->not->toBe($oldToken);
});

test('setup:reset-token displays token in output', function () {
    Setup::query()->update(['is_installed' => false]);

    $this->artisan('setup:reset-token')
        ->expectsOutputToContain(__('setup.cli.quick_access'))
        ->assertSuccessful();
});
