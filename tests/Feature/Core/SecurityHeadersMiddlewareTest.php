<?php

declare(strict_types=1);

use App\Modules\Core\Http\Middleware\SecurityHeadersMiddleware;
use App\Modules\Setup\Domain\Installation\Http\Middleware\RequireSetupAccessMiddleware;
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

function secureRequest(): Request
{
    return Request::create('https://internara.test/dashboard', 'GET');
}

function cspDirective(string $directive): string
{
    $csp = (string) runSecurityHeaders(Request::create('/dashboard', 'GET'))->headers->get('Content-Security-Policy');

    preg_match('/'.preg_quote($directive, '/').'\s+([^;]*);/', $csp, $matches);

    return trim($matches[1] ?? '');
}

describe('1PGM4: SecurityHeadersMiddleware', function (): void {
    test('1PGM4-FR-SEC-001, 1PGM4-FR-SEC-002: CSP header carries the self baseline', function (): void {
        $csp = runSecurityHeaders(Request::create('/dashboard', 'GET'))->headers->get('Content-Security-Policy');

        expect($csp)->not->toBeNull()
            ->and($csp)->toContain("default-src 'self'");
    });

    test('1PGM4-FR-SEC-003, 1PGM4-FR-SEC-015: script-src uses a per-request nonce instead of unsafe-inline', function (): void {
        $response = runSecurityHeaders(Request::create('/dashboard', 'GET'));
        $scriptSrc = cspDirective('script-src');
        $csp = (string) $response->headers->get('Content-Security-Policy');

        expect($scriptSrc)->toContain("'self'")
            ->and($scriptSrc)->toContain("'unsafe-eval'")
            ->and($scriptSrc)->toMatch("/'nonce-[A-Za-z0-9+\\/=]+'/")
            ->and($scriptSrc)->not->toContain("'unsafe-inline'")
            ->and($csp)->not->toContain("script-src 'self' 'unsafe-inline'");
    });

    test('1PGM4-FR-SEC-015: every request gets a different nonce', function (): void {
        preg_match(
            "/'nonce-([^']+)'/",
            cspDirective('script-src'),
            $first,
        );
        preg_match(
            "/'nonce-([^']+)'/",
            cspDirective('script-src'),
            $second,
        );

        expect($first[1])->not->toBe($second[1]);
    });

    test('1PGM4-FR-SEC-015, 1PGM4-NFR-SEC-001: rendered markup carries the same nonce as the header', function (): void {
        $this->withoutMiddleware(RequireSetupAccessMiddleware::class);

        $response = $this->get(route('login'));

        $response->assertOk();

        $csp = (string) $response->headers->get('Content-Security-Policy');

        expect($csp)->toMatch("/'nonce-([^']+)'/");

        preg_match("/'nonce-([^']+)'/", $csp, $matches);

        expect($response->getContent())->toContain('nonce="'.$matches[1].'"');
    });

    test('1PGM4-FR-SEC-003, 1PGM4-NFR-SEC-001: inline scripts without the nonce are not allowlisted', function (): void {
        config()->set('security-headers.csp_nonce_enabled', false);

        $scriptSrc = cspDirective('script-src');

        expect($scriptSrc)->not->toContain("'unsafe-inline'")
            ->and($scriptSrc)->not->toContain('nonce-');
    });

    test('1PGM4-FR-SEC-004, 1PGM4-DD-SEC-002: style-src keeps unsafe-inline for style attributes', function (): void {
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

        $response = runSecurityHeaders(secureRequest());

        expect($response->headers->get('Strict-Transport-Security'))
            ->toBe('max-age=31536000; includeSubDomains');
    });

    test('1PGM4-FR-SEC-016: HSTS is withheld on plain-HTTP requests even when enabled', function (): void {
        config()->set('security-headers.hsts_enabled', true);

        $response = runSecurityHeaders(Request::create('http://internara.test/dashboard', 'GET'));

        expect($response->headers->has('Strict-Transport-Security'))->toBeFalse();
    });

    test('1PGM4-FR-SEC-011, 1PGM4-DD-SEC-003: HSTS defaults on in production and off elsewhere', function (): void {
        $environment = $_SERVER['APP_ENV'] ?? null;
        $putenvEnvironment = getenv('APP_ENV');

        try {
            $_SERVER['APP_ENV'] = 'production';
            putenv('APP_ENV=production');
            $production = require config_path('security-headers.php');

            $_SERVER['APP_ENV'] = 'local';
            putenv('APP_ENV=local');
            $local = require config_path('security-headers.php');
        } finally {
            if ($environment === null) {
                unset($_SERVER['APP_ENV']);
            } else {
                $_SERVER['APP_ENV'] = $environment;
            }

            $putenvEnvironment === false
                ? putenv('APP_ENV')
                : putenv('APP_ENV='.$putenvEnvironment);
        }

        expect($production['hsts_enabled'])->toBeTrue()
            ->and($local['hsts_enabled'])->toBeFalse();
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

        $response = runSecurityHeaders(secureRequest());

        expect($response->headers->has('Strict-Transport-Security'))->toBeFalse();
    });

    test('1PGM4-FR-SEC-012 1PGM4-NFR-SEC-003: header values follow config and env overrides', function (): void {
        config()->set('security-headers.headers.X-Frame-Options', 'SAMEORIGIN');
        config()->set('security-headers.headers.X-Custom-Probe', 'probe-value');

        $response = runSecurityHeaders(Request::create('/dashboard', 'GET'));

        expect($response->headers->get('X-Frame-Options'))->toBe('SAMEORIGIN')
            ->and($response->headers->get('X-Custom-Probe'))->toBe('probe-value');
    });

    test('1PGM4-FR-SEC-014, 1PGM4-NFR-SEC-001, 1PGM4-UC-SEC-001, 1PGM4-UC-SEC-002: middleware preserves response body and html escaping holds', function (): void {
        $request = Request::create('/test', 'GET');
        $rawHtml = '<script>alert("xss")</script>';
        $response = app(SecurityHeadersMiddleware::class)->handle(
            $request,
            fn () => response(e($rawHtml), 200),
        );

        expect($response->getContent())->toBe('&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;')
            ->and($response->headers->get('X-Content-Type-Options'))->toBe('nosniff');
    });

    test('1PGM4-UC-SEC-003: auditor verifies the security posture by inspecting response headers', function (): void {
        $response = runSecurityHeaders(Request::create('/dashboard', 'GET'));

        expect($response->headers->has('X-Content-Type-Options'))->toBeTrue()
            ->and($response->headers->has('X-Frame-Options'))->toBeTrue()
            ->and($response->headers->has('Referrer-Policy'))->toBeTrue()
            ->and($response->headers->has('Content-Security-Policy'))->toBeTrue()
            ->and($response->headers->has('Permissions-Policy'))->toBeTrue();
    });

    test('1PGM4-DD-SEC-001, 1PGM4-DD-SEC-004: middleware-based injection with a nonce-based script policy', function (): void {
        $csp = config('security-headers.csp');

        expect($csp)->toContain("style-src 'self' 'unsafe-inline'")
            ->and($csp)->toContain("script-src 'self' 'unsafe-eval'")
            ->and($csp)->not->toContain("script-src 'self' 'unsafe-inline'");
    });
});
