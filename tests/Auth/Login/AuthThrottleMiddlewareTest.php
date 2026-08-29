<?php

declare(strict_types=1);

use App\Modules\Auth\Domain\Login\Http\Middleware\AuthThrottleMiddleware;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

uses(LazilyRefreshDatabase::class);

function authThrottleRun(Request $request): Response
{
    $next = fn (Request $r) => new Response('ok', 200);

    return (new AuthThrottleMiddleware)->handle($request, $next);
}

beforeEach(function () {
    RateLimiter::clear('login:127.0.0.1:'.md5(''));
    Config::set('auth.throttle.login_max_attempts', 5);
    Config::set('auth.throttle.login_decay_seconds', 60);
});

test('2CF4Y-FR-MW5: allows requests under the login rate limit', function () {
    $request = Request::create('/login', 'POST');
    $request->server->set('REMOTE_ADDR', '127.0.0.1');
    $request->merge(['identifier' => 'test@example.com']);

    $response = authThrottleRun($request);

    expect($response->getStatusCode())->toBe(200);
});

test('2CF4Y-FR-MW5: blocks login after exceeding 5 attempts per 60 seconds per IP', function () {
    $request = Request::create('/login', 'POST');
    $request->server->set('REMOTE_ADDR', '127.0.0.1');
    $request->merge(['identifier' => 'test@example.com']);

    for ($i = 0; $i < 5; $i++) {
        $response = authThrottleRun($request);
        expect($response->getStatusCode())->toBe(200);
    }

    $response = authThrottleRun($request);
    expect($response->getStatusCode())->toBe(302);
    expect($response->headers->get('Location'))->toContain('/login');
});

test('2CF4Y-FR-MW5: returns JSON error for JSON requests when rate limited', function () {
    $request = Request::create('/login', 'POST', [], [], [], ['HTTP_ACCEPT' => 'application/json']);
    $request->server->set('REMOTE_ADDR', '127.0.0.2');
    $request->merge(['identifier' => 'test@example.com']);

    for ($i = 0; $i < 5; $i++) {
        authThrottleRun($request);
    }

    $response = authThrottleRun($request);
    expect($response->getStatusCode())->toBe(429);
    expect($response->headers->get('Content-Type'))->toContain('application/json');
});

test('2CF4Y-FR-MW5: uses login_max_attempts and login_decay_seconds config for login routes', function () {
    Config::set('auth.throttle.login_max_attempts', 3);
    Config::set('auth.throttle.login_decay_seconds', 30);

    $request = Request::create('/login', 'POST');
    $request->server->set('REMOTE_ADDR', '127.0.0.3');
    $request->merge(['identifier' => 'test@example.com']);

    for ($i = 0; $i < 3; $i++) {
        expect(authThrottleRun($request)->getStatusCode())->toBe(200);
    }

    $response = authThrottleRun($request);
    expect($response->getStatusCode())->toBe(302);
    expect($response->headers->get('Location'))->toContain('/login');
});
