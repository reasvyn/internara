<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;

test('warms caches successfully', function () {
    $this->artisan('system:cache-warm')
        ->expectsOutputToContain(__('setup.system.cache_warm_starting'));
});

test('displays starting message', function () {
    $this->artisan('system:cache-warm')
        ->expectsOutputToContain(__('setup.system.cache_warm_starting'));
});
