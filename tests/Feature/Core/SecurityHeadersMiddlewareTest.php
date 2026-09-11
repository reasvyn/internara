<?php

declare(strict_types=1);

use App\Modules\Core\Http\Middleware\SecurityHeadersMiddleware;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

function runSecurityHeaders(Request $request): Response
{
    return app(SecurityHeadersMiddleware::class)->handle(
        $request,
        fn () => response('ok', 200),
    );
}

describe('1PGM4: SecurityHeadersMiddleware', function (): void {
    test('1PGM4-FR-SEC-004: style-src keeps unsafe-inline for Tailwind', function (): void {
        $response = runSecurityHeaders(Request::create('/dashboard', 'GET'));

        expect($response->headers->get('Content-Security-Policy'))
            ->toContain("style-src 'self' 'unsafe-inline'");
    });

    test('1PGM4-FR-SEC-005: img-src allows data and blob for uploads', function (): void {
        $response = runSecurityHeaders(Request::create('/dashboard', 'GET'));

        expect($response->headers->get('Content-Security-Policy'))
            ->toContain('img-src \'self\' data: blob:');
    });

    test('1PGM4-FR-SEC-006: HSTS carries one-year max-age with subdomains when enabled', function (): void {
        config()->set('security-headers.hsts_enabled', true);

        $response = runSecurityHeaders(Request::create('/dashboard', 'GET'));

        expect($response->headers->get('Strict-Transport-Security'))
            ->toBe('max-age=31536000; includeSubDomains');
    });

    test('1PGM4-FR-SEC-007: X-Frame-Options denies framing', function (): void {
        $response = runSecurityHeaders(Request::create('/dashboard', 'GET'));

        expect($response->headers->get('X-Frame-Options'))->toBe('DENY');
    });

    test('1PGM4-FR-SEC-008: Referrer-Policy limits cross-origin leakage', function (): void {
        $response = runSecurityHeaders(Request::create('/dashboard', 'GET'));

        expect($response->headers->get('Referrer-Policy'))
            ->toBe('strict-origin-when-cross-origin');
    });

    test('1PGM4-FR-SEC-009: Permissions-Policy disables camera, microphone and geolocation', function (): void {
        $response = runSecurityHeaders(Request::create('/dashboard', 'GET'));

        $policy = $response->headers->get('Permissions-Policy');

        expect($policy)->toContain('camera=()')
            ->and($policy)->toContain('microphone=()')
            ->and($policy)->toContain('geolocation=()');
    });

    test('1PGM4-FR-SEC-012: header values follow config overrides', function (): void {
        config()->set('security-headers.headers.X-Frame-Options', 'SAMEORIGIN');
        config()->set('security-headers.headers.X-Custom-Probe', 'probe-value');

        $response = runSecurityHeaders(Request::create('/dashboard', 'GET'));

        expect($response->headers->get('X-Frame-Options'))->toBe('SAMEORIGIN')
            ->and($response->headers->get('X-Custom-Probe'))->toBe('probe-value');
    });
});
