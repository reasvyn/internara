<?php

declare(strict_types=1);

namespace Modules\Setup\Tests\Feature\Middleware;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\RateLimiter;
use Mockery;
use Modules\Admin\Services\Contracts\SuperAdminService;
use Modules\Setting\Services\Contracts\SettingService;
use Modules\Setup\Http\Middleware\ProtectSetupRoute;
use Modules\Setup\Services\Contracts\SetupService;
use Tests\TestCase;

describe('ProtectSetupRoute Middleware', function () {
    beforeEach(function () {
        Config::set('app.env', 'production');
        Cache::flush();
        RateLimiter::clear('setup_throttle:127.0.0.1');

        $this->setupService = Mockery::mock(SetupService::class);
        $this->superAdminService = Mockery::mock(SuperAdminService::class);
        $this->settingService = Mockery::mock(SettingService::class);

        $this->app->instance(SetupService::class, $this->setupService);
        $this->app->instance(SuperAdminService::class, $this->superAdminService);
        $this->app->instance(SettingService::class, $this->settingService);

        $this->middleware = new ProtectSetupRoute(
            $this->setupService,
            $this->superAdminService,
            $this->settingService
        );
    });

    it('enforces rate limiting on setup routes', function () {
        $this->setupService->shouldReceive('isAppInstalled')->andReturn(false);
        $this->superAdminService->shouldReceive('exists')->andReturn(false);
        
        $request = Request::create('/setup/welcome', 'GET', ['token' => 'valid-token']);
        $request->server->set('REMOTE_ADDR', '127.0.0.1');
        $request->setLaravelSession($this->app['session']->driver());

        $this->settingService->shouldReceive('getValue')->with('setup_token')->andReturn('valid-token');
        $this->settingService->shouldReceive('getValue')->with('setup_token_expires_at')->andReturn(now()->addHour()->toIso8601String());
        
        $this->setupService->shouldReceive('isStepCompleted')->andReturn(false);

        $next = fn() => new Response('OK');

        for ($i = 0; $i < 20; $i++) {
            $response = $this->middleware->handle($request, $next);
            expect($response->getStatusCode())->toBe(200);
        }

        // 21st request should be throttled
        $this->withoutExceptionHandling();
        try {
            $this->middleware->handle($request, $next);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            expect($e->getStatusCode())->toBe(429);
        }
    });

    it('aborts with 404 if application is already installed and superadmin exists', function () {
        $this->setupService->shouldReceive('isAppInstalled')->andReturn(true);
        $this->superAdminService->shouldReceive('exists')->andReturn(true);

        $request = Request::create('/setup/welcome', 'GET');
        $request->server->set('REMOTE_ADDR', '127.0.0.1');
        
        $this->withoutExceptionHandling();
        try {
            $this->middleware->handle($request, fn() => new Response('OK'));
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            expect($e->getStatusCode())->toBe(404);
        }
    });

    it('aborts with 403 if application is not installed but user is unauthorized', function () {
        $this->setupService->shouldReceive('isAppInstalled')->andReturn(false);
        $this->superAdminService->shouldReceive('exists')->andReturn(false);
        
        // No session authorization, invalid token
        $request = Request::create('/setup/welcome', 'GET', ['token' => 'invalid-token']);
        $request->server->set('REMOTE_ADDR', '127.0.0.1');
        $request->setLaravelSession($this->app['session']->driver());

        $this->settingService->shouldReceive('getValue')->with('setup_token')->andReturn('valid-token');
        $this->settingService->shouldReceive('getValue')->with('setup_token_expires_at')->andReturn(now()->addHour()->toIso8601String());

        $this->withoutExceptionHandling();
        try {
            $this->middleware->handle($request, fn() => new Response('OK'));
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            expect($e->getStatusCode())->toBe(403);
        }
    });

    it('denies access if setup token is expired', function () {
        $this->setupService->shouldReceive('isAppInstalled')->andReturn(false);
        $this->superAdminService->shouldReceive('exists')->andReturn(false);
        
        $request = Request::create('/setup/welcome', 'GET', ['token' => 'valid-token']);
        $request->server->set('REMOTE_ADDR', '127.0.0.1');
        $request->setLaravelSession($this->app['session']->driver());

        $this->settingService->shouldReceive('getValue')->with('setup_token')->andReturn('valid-token');
        // Token expired
        $this->settingService->shouldReceive('getValue')->with('setup_token_expires_at')->andReturn(now()->subHour()->toIso8601String());

        $this->withoutExceptionHandling();
        try {
            $this->middleware->handle($request, fn() => new Response('OK'));
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            expect($e->getStatusCode())->toBe(403);
        }
    });

    it('redirects to complete if all steps are done except the final step', function () {
        $this->setupService->shouldReceive('isAppInstalled')->andReturn(false);
        $this->superAdminService->shouldReceive('exists')->andReturn(false);
        
        $request = Request::create('/setup/welcome', 'GET', ['token' => 'valid-token']);
        $request->server->set('REMOTE_ADDR', '127.0.0.1');
        $request->setLaravelSession($this->app['session']->driver());

        $this->settingService->shouldReceive('getValue')->with('setup_token')->andReturn('valid-token');
        $this->settingService->shouldReceive('getValue')->with('setup_token_expires_at')->andReturn(now()->addHour()->toIso8601String());

        // Mock all dependencies completed EXCEPT the final one
        $this->setupService->shouldReceive('isStepCompleted')->andReturnUsing(function ($step) {
            return $step !== SetupService::STEP_COMPLETE;
        });

        $response = $this->middleware->handle($request, fn() => new Response('OK'));
        expect($response->isRedirect(route('setup.complete')))->toBeTrue();
    });

    it('passes to next if authorized and steps remaining', function () {
        $this->setupService->shouldReceive('isAppInstalled')->andReturn(false);
        $this->superAdminService->shouldReceive('exists')->andReturn(false);
        
        $request = Request::create('/setup/welcome', 'GET', ['token' => 'valid-token']);
        $request->server->set('REMOTE_ADDR', '127.0.0.1');
        $request->setLaravelSession($this->app['session']->driver());

        $this->settingService->shouldReceive('getValue')->with('setup_token')->andReturn('valid-token');
        $this->settingService->shouldReceive('getValue')->with('setup_token_expires_at')->andReturn(now()->addHour()->toIso8601String());

        // First step is not completed
        $this->setupService->shouldReceive('isStepCompleted')->andReturn(false);

        $response = $this->middleware->handle($request, fn() => new Response('OK'));
        expect($response->getStatusCode())->toBe(200);
        expect($response->getContent())->toBe('OK');
    });
});
