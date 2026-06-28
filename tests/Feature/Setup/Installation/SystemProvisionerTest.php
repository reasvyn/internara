<?php

declare(strict_types=1);

namespace Tests\Feature\Setup\Installation\Support;

use App\Setup\Installation\Services\SystemProvisioner;
use Database\Seeders\SetupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Mockery;

uses(RefreshDatabase::class);

test('system provisioner tasks list is complete', function () {
    $provisioner = new SystemProvisioner;
    $tasks = $provisioner->getTasks();

    expect($tasks)->toHaveKeys([
        'ensure_env',
        'generate_key',
        'run_migrations',
        'run_seeders',
        'storage_link',
        'clear_cache',
    ]);
});

test('ensure_env copies env.example if env does not exist', function () {
    File::shouldReceive('exists')->with(base_path('.env'))->once()->andReturn(false);

    File::shouldReceive('exists')->with(base_path('.env.example'))->once()->andReturn(true);

    File::shouldReceive('copy')
        ->with(base_path('.env.example'), base_path('.env'))
        ->once()
        ->andReturn(true);

    $provisioner = new SystemProvisioner;
    $provisioner->executeTask('ensure_env');
});

test('ensure_env does nothing if env already exists', function () {
    File::shouldReceive('exists')->with(base_path('.env'))->once()->andReturn(true);

    $provisioner = new SystemProvisioner;
    $provisioner->executeTask('ensure_env');

    expect(true)->toBeTrue(); // Assertion to check no exceptions thrown
});

test('generate_key calls key:generate if APP_KEY is empty', function () {
    File::shouldReceive('get')->with(base_path('.env'))->once()->andReturn('DB_CONNECTION=sqlite'); // Env content without APP_KEY

    Artisan::shouldReceive('call')->with('key:generate')->once()->andReturn(0);

    $provisioner = new SystemProvisioner;
    $provisioner->executeTask('generate_key');
});

test('run_migrations calls migrate when force is false', function () {
    Artisan::shouldReceive('call')
        ->with('migrate', ['--force' => true])
        ->once()
        ->andReturn(0);

    $provisioner = new SystemProvisioner;
    $provisioner->executeTask('run_migrations', false);
});

test('run_migrations calls migrate:fresh when force is true', function () {
    Artisan::shouldReceive('call')
        ->with('migrate:fresh', ['--force' => true])
        ->once()
        ->andReturn(0);

    $provisioner = new SystemProvisioner;
    $provisioner->executeTask('run_migrations', true);
});

test('run_seeders executes SetupSeeder', function () {
    $seederMock = Mockery::mock(SetupSeeder::class);
    $seederMock->shouldReceive('run')->once();

    app()->instance(SetupSeeder::class, $seederMock);

    $provisioner = new SystemProvisioner;
    $provisioner->executeTask('run_seeders');
});

test('storage_link calls storage:link if public/storage does not exist', function () {
    // If public_path('storage') does not exist
    if (! file_exists(public_path('storage'))) {
        Artisan::shouldReceive('call')->with('storage:link')->once()->andReturn(0);
    } else {
        // If it already exists, storage:link is not called
        Artisan::shouldReceive('call')->with('storage:link')->never();
    }

    $provisioner = new SystemProvisioner;
    $provisioner->executeTask('storage_link');
    expect(true)->toBeTrue();
});

test('clear_cache calls config, cache, route, and view clear commands', function () {
    Artisan::shouldReceive('call')->with('config:clear')->once()->andReturn(0);
    Artisan::shouldReceive('call')->with('cache:clear')->once()->andReturn(0);
    Artisan::shouldReceive('call')->with('route:clear')->once()->andReturn(0);
    Artisan::shouldReceive('call')->with('view:clear')->once()->andReturn(0);

    $provisioner = new SystemProvisioner;
    $provisioner->executeTask('clear_cache');
});
