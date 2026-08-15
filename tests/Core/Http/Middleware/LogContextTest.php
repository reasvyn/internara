<?php

declare(strict_types=1);

use App\Core\Http\Middleware\LogContextMiddleware;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

uses(LazilyRefreshDatabase::class);

function logContextRun(Request $request): Response
{
    $next = fn (Request $r) => new Response('ok', 201);

    return (new LogContextMiddleware)->handle($request, $next);
}

it('FR-LC1/FR-LC2/FR-LC4/FR-LC5: injects request_id, method, url, ip, duration and status into log context', function () {
    $logs = captureLogs();
    $request = Request::create('/some/path?q=1', 'GET');
    $request->server->set('REMOTE_ADDR', '127.0.0.1');

    logContextRun($request);
    Log::info('after middleware');

    $entry = $logs->last();

    expect($entry->context['request_id'])->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/');
    expect($entry->context['method'])->toBe('GET');
    expect($entry->context['url'])->toBe('http://localhost/some/path?q=1');
    expect($entry->context['ip'])->toBe('127.0.0.1');
    expect($entry->context['status'])->toBe(201);
    expect($entry->context['duration_ms'])->toBeNumeric();
});

it('FR-LC3: injects user_id and user_role when a user is authenticated', function () {
    $logs = captureLogs();
    $user = User::factory()->create()->assignRole('admin');
    $request = Request::create('/');
    $request->setUserResolver(fn () => $user);

    logContextRun($request);
    Log::info('after middleware');

    $entry = $logs->last();

    expect($entry->context['user_id'])->toBe($user->id);
    expect($entry->context['user_role'])->toBe('admin');
});

it('FR-LC3: omits user context for guest requests', function () {
    $logs = captureLogs();
    $request = Request::create('/');

    logContextRun($request);
    Log::info('after middleware');

    $entry = $logs->last();

    expect($entry->context)->not->toHaveKey('user_id');
    expect($entry->context)->not->toHaveKey('user_role');
});
