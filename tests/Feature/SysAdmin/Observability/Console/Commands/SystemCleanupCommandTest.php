<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

uses(LazilyRefreshDatabase::class);

test('runs cleanup tasks when confirmed', function () {
    Artisan::shouldReceive('call')
        ->with('auth:clear-resets')
        ->andReturn(0);
    Artisan::shouldReceive('call')
        ->with('cache:prune-stale-tags')
        ->andReturn(0);
    Artisan::shouldReceive('call')
        ->with('queue:prune-failed')
        ->andReturn(0);
    Artisan::shouldReceive('call')
        ->with('activitylog:clean')
        ->andReturn(0);
    Artisan::shouldReceive('call')
        ->with('media-library:clean')
        ->andReturn(0);

    $this->artisan('system:cleanup', ['--force' => true])
        ->assertExitCode(0)
        ->expectsOutputToContain(__('setup.system.cleanup_completed'));
});

test('aborts when confirmation is declined', function () {
    $this->artisan('system:cleanup')
        ->expectsConfirmation(__('setup.system.cleanup_confirm'), 'no')
        ->assertExitCode(0);
});

test('cleans up old log files', function () {
    File::shouldReceive('glob')
        ->with(storage_path('logs/laravel-*.log'))
        ->andReturn([storage_path('logs/laravel-old.log')]);
    File::shouldReceive('lastModified')
        ->andReturn(now()->subDays(60)->timestamp);
    File::shouldReceive('delete')
        ->with(storage_path('logs/laravel-old.log'))
        ->andReturnTrue();

    $this->artisan('system:cleanup', ['--force' => true])
        ->assertExitCode(0);
});

test('handles task failures gracefully', function () {
    Artisan::shouldReceive('call')
        ->with('auth:clear-resets')
        ->andThrow(new RuntimeException('Failed'));

    $this->artisan('system:cleanup', ['--force' => true])
        ->assertExitCode(0);
});
