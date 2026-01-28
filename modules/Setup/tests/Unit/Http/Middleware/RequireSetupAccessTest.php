<?php

declare(strict_types=1);

namespace Modules\Setup\Tests\Unit\Http\Middleware;

use Illuminate\Http\Request;
use Mockery;
use Modules\Setup\Http\Middleware\RequireSetupAccess;
use Modules\Setup\Services\Contracts\SetupService;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function () {
    $this->setupService = Mockery::mock(SetupService::class);
    $this->middleware = new RequireSetupAccess($this->setupService);
});

test('it aborts 404 if app is installed and accessing setup route', function () {
    $this->setupService->shouldReceive('isAppInstalled')->andReturn(true);

    $request = Request::create('/setup/welcome', 'GET');
    $routeMock = Mockery::mock();
    $routeMock->shouldReceive('named')->with('setup.*')->andReturn(true);
    $request->setRouteResolver(fn () => $routeMock);

    try {
        $this->middleware->handle($request, fn () => null);
    } catch (HttpException $e) {
        expect($e->getStatusCode())->toBe(404);

        return;
    }

    $this->fail('Middleware did not abort with 404');
});

test('it redirects to setup welcome if app not installed and on non-setup route', function () {
    if (app()->runningInConsole()) {
        $this->markTestSkipped('Cannot test redirection in Unit context while running in console.');
    }

    $this->setupService->shouldReceive('isAppInstalled')->andReturn(false);

    $request = Request::create('/dashboard', 'GET');
    $routeMock = Mockery::mock();
    $routeMock->shouldReceive('named')->with('setup.*')->andReturn(false);
    $request->setRouteResolver(fn () => $routeMock);

    $response = $this->middleware->handle(
        $request,
        fn () => new \Symfony\Component\HttpFoundation\Response('passed'),
    );

    expect($response->isRedirect())->toBeTrue();
});

test('it allows access if already on setup route and not installed', function () {
    $this->setupService->shouldReceive('isAppInstalled')->andReturn(false);

    $request = Request::create('/setup/welcome', 'GET');
    $routeMock = Mockery::mock();
    $routeMock->shouldReceive('named')->with('setup.*')->andReturn(true);
    $request->setRouteResolver(fn () => $routeMock);

    $response = $this->middleware->handle(
        $request,
        fn () => new \Symfony\Component\HttpFoundation\Response('passed'),
    );

    expect($response->getContent())->toBe('passed');
});
