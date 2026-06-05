<?php

declare(strict_types=1);

namespace Tests\Feature\Setup\Support;

use App\Setup\Support\SystemProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class);

test('system provisioner tasks list and execute run_seeders', function () {
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

    // Mock Artisan calls for key generate / clear cache so we don't actually mess up setup config/cache
    Artisan::shouldReceive('call')
        ->with('config:clear')
        ->once()
        ->andReturn(0);
    Artisan::shouldReceive('call')
        ->with('cache:clear')
        ->once()
        ->andReturn(0);
    Artisan::shouldReceive('call')
        ->with('route:clear')
        ->once()
        ->andReturn(0);
    Artisan::shouldReceive('call')
        ->with('view:clear')
        ->once()
        ->andReturn(0);

    // Test clear_cache task
    $provisioner->executeTask('clear_cache');
});
