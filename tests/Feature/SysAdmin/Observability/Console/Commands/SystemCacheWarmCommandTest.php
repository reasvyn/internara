<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;

test('warms caches successfully', function () {
    Artisan::shouldReceive('call')
        ->with('config:cache')
        ->andReturn(0);
    Artisan::shouldReceive('call')
        ->with('view:cache')
        ->andReturn(0);
    Artisan::shouldReceive('call')
        ->with('event:cache')
        ->andReturn(0);

    $this->artisan('system:cache-warm')
        ->assertExitCode(0)
        ->expectsOutputToContain(__('setup.system.cache_warm_completed'));
});

test('displays starting message', function () {
    $this->artisan('system:cache-warm')
        ->expectsOutputToContain(__('setup.system.cache_warm_starting'));
});
