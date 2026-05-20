<?php

declare(strict_types=1);

use App\Domain\Core\Http\Middleware\SecurityHeaders;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

describe('SecurityHeaders middleware', function () {
    it('adds security headers from config', function () {
        config(['security-headers.headers' => [
            'X-Test-Header' => 'test-value',
        ]]);
        config(['security-headers.csp_enabled' => false]);

        $middleware = new SecurityHeaders;
        $request = Request::create('/');
        $response = new Response('ok');

        $result = $middleware->handle($request, fn ($req) => $response);

        expect($result->headers->get('X-Test-Header'))->toBe('test-value');
    });

    it('adds CSP header when enabled', function () {
        config(['security-headers.headers' => []]);
        config(['security-headers.csp_enabled' => true]);
        config(['security-headers.csp' => "default-src 'self'"]);

        $middleware = new SecurityHeaders;
        $request = Request::create('/');

        $result = $middleware->handle($request, fn ($req) => new Response('ok'));

        expect($result->headers->get('Content-Security-Policy'))->toBe("default-src 'self'");
    });

    it('skips CSP header when disabled', function () {
        config(['security-headers.headers' => []]);
        config(['security-headers.csp_enabled' => false]);

        $middleware = new SecurityHeaders;

        $result = $middleware->handle(
            Request::create('/'),
            fn ($req) => new Response('ok'),
        );

        expect($result->headers->has('Content-Security-Policy'))->toBeFalse();
    });
});
