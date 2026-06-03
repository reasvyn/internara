<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Setup;

use App\Domain\Admin\Aggregates\Setup\Models\Setup;
use App\Domain\Admin\Aggregates\Setup\Support\SystemProvisioner;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

test('setup install command warns and exits if already installed', function () {
    $this->artisan('setup:install')
        ->expectsOutputToContain(__('setup.cli.already_installed'))
        ->assertFailed();
});

test('setup install command can force install in testing environment', function () {
    $mockProvisioner = Mockery::mock(SystemProvisioner::class);
    $mockProvisioner->shouldReceive('getTasks')->andReturn([
        'ensure_env' => 'Ensure env',
    ]);
    $mockProvisioner->shouldReceive('executeTask')->withAnyArgs()->andReturnNull();
    $mockProvisioner->shouldReceive('executeAll')->withAnyArgs()->andReturnNull();

    $this->instance(SystemProvisioner::class, $mockProvisioner);

    $this->artisan('setup:install --force')
        ->assertSuccessful();
});

test('setup install command check-only option runs audit but does not provision', function () {
    Setup::query()->update(['is_installed' => false]);

    $this->artisan('setup:install --check-only')
        ->expectsOutputToContain(__('setup.cli.check_only_complete'))
        ->assertSuccessful();
});

test('setup reset-token command fails if setups table does not exist', function () {
    Schema::dropIfExists('setups');

    $this->artisan('setup:reset-token')
        ->expectsOutputToContain(__('setup.reset_token.table_missing'))
        ->assertFailed();
});

test('setup reset-token command fails if already installed', function () {
    $this->artisan('setup:reset-token')
        ->expectsOutputToContain(__('setup.reset_token.protected'))
        ->assertFailed();
});

test('setup reset-token command generates token if not installed', function () {
    Setup::query()->update(['is_installed' => false]);

    $this->artisan('setup:reset-token')
        ->expectsOutputToContain(__('setup.reset_token.new_token_generated'))
        ->assertSuccessful();

    $setup = Setup::first();
    expect($setup->setup_token)->not->toBeNull();
});

test('setup install command initiates all standard roles in database', function () {
    Role::query()->delete();

    $mockProvisioner = Mockery::mock(SystemProvisioner::class);
    $mockProvisioner->shouldReceive('getTasks')->andReturn([
        'ensure_env' => 'Ensure env',
        'run_seeders' => 'Run seeders',
    ]);
    $mockProvisioner->shouldReceive('executeTask')->withAnyArgs()->andReturnUsing(function ($task, $force = false) {
        if ($task === 'run_seeders') {
            $seeder = new RolePermissionSeeder;
            $seeder->run();
        }
    });
    $mockProvisioner->shouldReceive('executeAll')->withAnyArgs()->andReturnNull();

    $this->instance(SystemProvisioner::class, $mockProvisioner);

    $this->artisan('setup:install --force')
        ->assertSuccessful();

    expect(Role::where('name', 'superadmin')->exists())->toBeTrue();
    expect(Role::where('name', 'admin')->exists())->toBeTrue();
    expect(Role::where('name', 'student')->exists())->toBeTrue();
    expect(Role::where('name', 'teacher')->exists())->toBeTrue();
    expect(Role::where('name', 'supervisor')->exists())->toBeTrue();
});
