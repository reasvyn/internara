<?php

declare(strict_types=1);

use App\Core\Http\Middleware\LogContext;
use App\Core\Http\Middleware\SecurityHeaders;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

test('LogContext middleware adds request context to logs', function () {
    $request = Request::create('/test-route', 'GET');
    $middleware = new LogContext;

    Log::shouldReceive('withContext')
        ->twice(); // once before next(), once after next()

    $response = $middleware->handle($request, function () {
        return new Response('Content', 200);
    });

    expect($response->getStatusCode())->toBe(200);
});

test('SecurityHeaders middleware adds headers from configuration', function () {
    config([
        'security-headers.headers' => [
            'X-Frame-Options' => 'DENY',
            'X-Content-Type-Options' => 'nosniff',
        ],
        'security-headers.csp_enabled' => true,
        'security-headers.csp' => "default-src 'self';",
    ]);

    $request = Request::create('/test-security', 'GET');
    $middleware = new SecurityHeaders;

    $response = $middleware->handle($request, function () {
        return new Response('Secure Content', 200);
    });

    expect($response->headers->get('X-Frame-Options'))->toBe('DENY');
    expect($response->headers->get('X-Content-Type-Options'))->toBe('nosniff');
    expect($response->headers->get('Content-Security-Policy'))->toBe("default-src 'self';");
});

test('SecurityHeaders middleware injects Vite dev URL when hot file exists', function () {
    config([
        'security-headers.headers' => [],
        'security-headers.csp_enabled' => true,
        'security-headers.csp' => "default-src 'self'; script-src 'self';",
    ]);

    $hotPath = public_path('hot');
    File::shouldReceive('exists')
        ->with($hotPath)
        ->andReturn(true);

    File::shouldReceive('get')
        ->with($hotPath)
        ->andReturn("http://localhost:5173\n");

    $request = Request::create('/test-security-vite', 'GET');
    $middleware = new SecurityHeaders;

    $response = $middleware->handle($request, function () {
        return new Response('Vite Content', 200);
    });

    $csp = $response->headers->get('Content-Security-Policy');
    expect($csp)->toContain('http://localhost:5173');
});
