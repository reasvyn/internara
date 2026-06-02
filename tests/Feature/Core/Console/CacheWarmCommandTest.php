<?php

declare(strict_types=1);

namespace Tests\Feature\Core\Console;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

describe('CacheWarmCommand', function () {
    it('runs successfully', function () {
        $this->artisan('system:cache-warm')
            ->assertSuccessful();
    });

    it('warms settings cache', function () {
        $this->artisan('system:cache-warm')
            ->expectsOutputToContain(__('setup.system.cache_warm_settings'));
    });

    it('warms brand cache', function () {
        $this->artisan('system:cache-warm')
            ->expectsOutputToContain(__('setup.system.cache_warm_brand'));
    });

    it('warms config cache', function () {
        $this->artisan('system:cache-warm')
            ->expectsOutputToContain(__('setup.system.cache_warm_config'));
    });

    it('warms view cache', function () {
        $this->artisan('system:cache-warm')
            ->expectsOutputToContain(__('setup.system.cache_warm_views'));
    });

    it('warms event cache', function () {
        $this->artisan('system:cache-warm')
            ->expectsOutputToContain(__('setup.system.cache_warm_events'));
    });

    it('outputs completion message', function () {
        $this->artisan('system:cache-warm')
            ->expectsOutputToContain(__('setup.system.cache_warm_completed'));
    });

    it('outputs starting message', function () {
        $this->artisan('system:cache-warm')
            ->expectsOutputToContain(__('setup.system.cache_warm_starting'));
    });
});
