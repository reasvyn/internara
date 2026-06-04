<?php

declare(strict_types=1);

namespace Tests\Feature\SysAdmin\Setup;

use App\Domain\SysAdmin\Aggregates\Setup\Models\Setup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

test('setup:reset-token command fails if setups table does not exist', function () {
    Schema::dropIfExists('setups');

    $this->artisan('setup:reset-token')
        ->expectsOutputToContain(__('setup.reset_token.table_missing'))
        ->assertFailed();
});

test('setup:reset-token command blocks execution if system is already installed', function () {
    $this->artisan('setup:reset-token')
        ->expectsOutputToContain(__('setup.reset_token.protected'))
        ->assertFailed();
});

test('setup:reset-token command successfully generates setup token if system not installed', function () {
    Setup::query()->update(['is_installed' => false]);

    $this->artisan('setup:reset-token')
        ->expectsOutputToContain(__('setup.reset_token.new_token_generated'))
        ->assertSuccessful();

    $setup = Setup::first();
    expect($setup->setup_token)->not->toBeNull();
});
