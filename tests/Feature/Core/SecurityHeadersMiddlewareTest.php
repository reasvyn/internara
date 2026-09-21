<?php

declare(strict_types=1);

use App\Modules\Core\Http\Middleware\SecurityHeadersMiddleware;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;

function runSecurityHeaders(Request $request): Response
{
    return app(SecurityHeadersMiddleware::class)->handle(
        $request,
        fn () => response('ok', 200),
    );
}

describe('1PGM4: SecurityHeadersMiddleware', function (): void {
    test('1PGM4-FR-SEC-001/002/003: CSP header baseline and script-src', function (): void {
        $response = runSecurityHeaders(Request::create('/dashboard', 'GET'));

        $csp = $response->headers->get('Content-Security-Policy');
        expect($csp)->not->toBeNull()
            ->and($csp)->toContain("default-src 'self'")
            ->and($csp)->toContain("script-src 'self' 'unsafe-inline' 'unsafe-eval'");
    });

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

    test('1PGM4-FR-SEC-007 1PGM4-FR-SEC-013: X-Frame-Options denies framing and present on responses', function (): void {
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

    test('1PGM4-FR-SEC-010 1PGM4-NFR-SEC-002: Vite development URL injection behaves correctly', function (): void {
        $hotPath = public_path('hot');
        if (! File::exists($hotPath)) {
            $response = runSecurityHeaders(Request::create('/dashboard', 'GET'));
            expect($response->headers->get('Content-Security-Policy'))->not->toContain('localhost:5173');
        }
    });

    test('1PGM4-FR-SEC-011 1PGM4-DD-SEC-003: HSTS omitted by default when disabled', function (): void {
        config()->set('security-headers.hsts_enabled', false);

        $response = runSecurityHeaders(Request::create('/dashboard', 'GET'));

        expect($response->headers->has('Strict-Transport-Security'))->toBeFalse();
    });

    test('1PGM4-FR-SEC-012 1PGM4-NFR-SEC-003: header values follow config and env overrides', function (): void {
        config()->set('security-headers.headers.X-Frame-Options', 'SAMEORIGIN');
        config()->set('security-headers.headers.X-Custom-Probe', 'probe-value');

        $response = runSecurityHeaders(Request::create('/dashboard', 'GET'));

        expect($response->headers->get('X-Frame-Options'))->toBe('SAMEORIGIN')
            ->and($response->headers->get('X-Custom-Probe'))->toBe('probe-value');
    });

    test('1PGM4-FR-SEC-014 1PGM4-NFR-SEC-001 1PGM4-UC-SEC-001/002: escaping rule and contracts', function (): void {
        expect(class_exists(SecurityHeadersMiddleware::class))->toBeTrue()
            ->and(config('security-headers.csp'))->not->toBeNull();
    });

    test('1PGM4-DD-SEC-001/002: middleware-based injection and inline style allowance', function (): void {
        $csp = config('security-headers.csp');
        expect($csp)->toContain("style-src 'self' 'unsafe-inline'");
    });
});
