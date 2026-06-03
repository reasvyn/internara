<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

test('domain:discover command runs successfully', function () {
    $this->artisan('domain:discover')
        ->assertExitCode(0);
});

test('system:health command runs successfully', function () {
    $this->artisan('system:health --json')
        ->assertExitCode(0);
});

test('system:cleanup command runs successfully with force option', function () {
    // Create a dummy log file to verify log pruning works
    $logDir = storage_path('logs');
    if (! File::isDirectory($logDir)) {
        File::makeDirectory($logDir, 0755, true);
    }
    $dummyLog = $logDir.'/laravel-2000-01-01.log';
    File::put($dummyLog, 'dummy content');
    // Set file modification time to 40 days ago
    touch($dummyLog, time() - (40 * 24 * 60 * 60));

    $this->artisan('system:cleanup --force --log-retention=30')
        ->assertExitCode(0);

    expect(File::exists($dummyLog))->toBeFalse();
});

test('system:cleanup command aborts when confirmation is declined', function () {
    $this->artisan('system:cleanup')
        ->expectsConfirmation(__('setup.system.cleanup_confirm'), 'no')
        ->assertExitCode(0);
});

test('system:cleanup command runs when confirmation is accepted', function () {
    $this->artisan('system:cleanup')
        ->expectsConfirmation(__('setup.system.cleanup_confirm'), 'yes')
        ->assertExitCode(0);
});

test('system:health command runs in standard mode', function () {
    $this->artisan('system:health')
        ->assertSuccessful();
});

test('system:cache-warm command runs successfully', function () {
    // Mock the inner Artisan calls to prevent caching configuration/views/events during testing
    Artisan::shouldReceive('call')
        ->with('config:cache')
        ->andReturn(0);
    Artisan::shouldReceive('call')
        ->with('view:cache')
        ->andReturn(0);
    Artisan::shouldReceive('call')
        ->with('event:cache')
        ->andReturn(0);

    // Permit other calls
    Artisan::shouldReceive('call')->byDefault();

    $this->artisan('system:cache-warm')
        ->assertExitCode(0);
});
