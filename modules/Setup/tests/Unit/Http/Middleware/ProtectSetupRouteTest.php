<?php

declare(strict_types=1);

namespace Modules\Setup\Tests\Unit\Http\Middleware;

use Illuminate\Http\Request;
use Modules\Setting\Services\Contracts\SettingService;
use Modules\Setup\Http\Middleware\ProtectSetupRoute;
use Modules\Setup\Services\Contracts\SetupService;
use Modules\User\Services\Contracts\SuperAdminService;
use Mockery;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function () {
    $this->setupService = Mockery::mock(SetupService::class);
    $this->superAdminService = Mockery::mock(SuperAdminService::class);
    $this->settingService = Mockery::mock(SettingService::class);
    $this->middleware = new ProtectSetupRoute(
        $this->setupService,
        $this->superAdminService,
        $this->settingService
    );
});

test('it aborts 404 if app is installed and superadmin exists', function () {
    $this->setupService->shouldReceive('isAppInstalled')->andReturn(true);
    $this->superAdminService->shouldReceive('remember')->andReturn(true);

    $request = Request::create('/setup/welcome', 'GET');

    try {
        $this->middleware->handle($request, fn() => null);
    } catch (HttpException $e) {
        expect($e->getStatusCode())->toBe(404);
        return;
    }

    $this->fail('Middleware did not abort with 404');
});

test('it allows access if app not installed and token is valid', function () {
    $this->setupService->shouldReceive('isAppInstalled')->andReturn(false);
    $this->settingService->shouldReceive('getValue')->with('setup_token')->andReturn('valid-token');

    $request = Request::create('/setup/welcome', 'GET', ['token' => 'valid-token']);
    $request->setLaravelSession(app('session')->driver('array'));

    $response = $this->middleware->handle($request, fn($req) => 'passed');

    expect($response)->toBe('passed')
        ->and($request->session()->get('setup_authorized'))->toBeTrue();
});

test('it aborts 403 if app not installed and no valid session/token', function () {
    $this->setupService->shouldReceive('isAppInstalled')->andReturn(false);
    $this->settingService->shouldReceive('getValue')->with('setup_token')->andReturn('valid-token');

    $request = Request::create('/setup/welcome', 'GET'); // No token
    $request->setLaravelSession(app('session')->driver('array'));

    try {
        $this->middleware->handle($request, fn() => null);
    } catch (HttpException $e) {
        expect($e->getStatusCode())->toBe(403);
        return;
    }

    $this->fail('Middleware did not abort with 403');
});

test('it aborts 403 if setup_token is missing in DB (completed)', function () {
    $this->setupService->shouldReceive('isAppInstalled')->andReturn(false);
    // Token exists in session but PURGED from DB
    $this->settingService->shouldReceive('getValue')->with('setup_token')->andReturn(null);

    $request = Request::create('/setup/welcome', 'GET');
    $session = app('session')->driver('array');
    $session->put('setup_authorized', true);
    $request->setLaravelSession($session);

    try {
        $this->middleware->handle($request, fn() => null);
    } catch (HttpException $e) {
        expect($e->getStatusCode())->toBe(403);
        return;
    }

    $this->fail('Middleware did not abort with 403 when token was purged');
});
