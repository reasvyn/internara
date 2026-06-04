<?php

declare(strict_types=1);

namespace Tests\Feature\SysAdmin\Setup;

use App\Domain\SysAdmin\Aggregates\Setup\Models\Setup;
use App\Domain\SysAdmin\Aggregates\Setup\Support\SystemProvisioner;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

test('setup:install command warns and exits if system is already installed', function () {
    $this->artisan('setup:install')
        ->expectsOutputToContain(__('setup.cli.already_installed'))
        ->assertFailed();
});

test('setup:install command run audit only with --check-only option without provisioning', function () {
    Setup::query()->update(['is_installed' => false]);

    $this->artisan('setup:install --check-only')
        ->expectsOutputToContain(__('setup.cli.check_only_complete'))
        ->assertSuccessful();
});

test('setup:install command forces system provisioning in testing mode', function () {
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

test('setup:install command initiates all 5 standard roles in database', function () {
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
