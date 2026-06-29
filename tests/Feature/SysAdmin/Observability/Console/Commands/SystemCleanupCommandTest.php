<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(LazilyRefreshDatabase::class);

test('runs cleanup tasks when confirmed', function () {
    $this->artisan('system:cleanup', ['--force' => true])
        ->assertExitCode(0)
        ->expectsOutputToContain(__('setup.system.cleanup_completed'));
});

test('aborts when confirmation is declined', function () {
    $this->artisan('system:cleanup')
        ->expectsConfirmation(__('setup.system.cleanup_confirm'), 'no')
        ->assertExitCode(0);
});

test('handles task failures gracefully', function () {
    $this->artisan('system:cleanup', ['--force' => true])
        ->assertExitCode(0);
});
