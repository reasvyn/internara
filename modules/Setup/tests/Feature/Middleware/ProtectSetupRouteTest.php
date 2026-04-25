<?php

declare(strict_types=1);

namespace Modules\Setup\Tests\Feature\Middleware;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\RateLimiter;
use Modules\Setup\Services\Contracts\SetupService;
use Tests\TestCase;

describe('ProtectSetupRoute Middleware', function () {
    it('enforces rate limiting on setup routes', function () {
        $setupService = Mockery::mock(SetupService::class);
        $setupService->shouldReceive('isAppInstalled')->andReturn(false);
        $this->app->instance(SetupService::class, $setupService);
        
        Config::set('app.env', 'production');
        
        // Mock session and token for access
        session(['setup_authorized' => true]);
        $this->app->instance(\Modules\Setting\Services\Contracts\SettingService::class, tap(Mockery::mock(\Modules\Setting\Services\Contracts\SettingService::class), function ($mock) {
            $mock->shouldReceive('getValue')->with('setup_token')->andReturn('test-token');
        }));

        // Hit the route 20 times (allowed)
        for ($i = 0; $i < 20; $i++) {
            $this->get(route('setup.welcome'))->assertStatus(200);
        }

        // 21st hit should be throttled
        $this->get(route('setup.welcome'))->assertStatus(429);
    });
});
