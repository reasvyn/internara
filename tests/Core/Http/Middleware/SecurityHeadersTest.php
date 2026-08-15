<?php

declare(strict_types=1);

use App\Core\Http\Middleware\SecurityHeadersMiddleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\Response;

function securityHeadersRun(?Request $request = null): Response
{
    $request ??= Request::create('/');
    $next = fn (Request $r) => new Response('ok');

    return (new SecurityHeadersMiddleware)->handle($request, $next);
}

it('1PGM4-FR-SEC1/FR-SEC2: every response carries a CSP header with default-src self', function () {
    $response = securityHeadersRun();

    expect($response->headers->get('Content-Security-Policy'))->not->toBeNull();
    expect($response->headers->get('Content-Security-Policy'))->toContain("default-src 'self'");
});

it('1PGM4-FR-SEC3/FR-SEC4: CSP includes script-src and style-src unsafe-inline for Livewire/Tailwind', function () {
    $csp = securityHeadersRun()->headers->get('Content-Security-Policy');

    expect($csp)->toContain('script-src');
    expect($csp)->toContain("style-src 'self' 'unsafe-inline'");
});

it('1PGM4-FR-SEC5: CSP allows self, data and blob sources for images', function () {
    $csp = securityHeadersRun()->headers->get('Content-Security-Policy');

    expect($csp)->toContain("img-src 'self' data: blob:");
});

it('1PGM4-FR-SEC7/FR-SEC8/FR-SEC9: X-Frame-Options, Referrer-Policy and Permissions-Policy are set', function () {
    $response = securityHeadersRun();

    expect($response->headers->get('X-Frame-Options'))->toBe('DENY');
    expect($response->headers->get('Referrer-Policy'))->toBe('strict-origin-when-cross-origin');
    expect($response->headers->get('Permissions-Policy'))->toContain('camera=()');
    expect($response->headers->get('X-Content-Type-Options'))->toBe('nosniff');
});

it('1PGM4-FR-SEC11: HSTS is omitted by default', function () {
    config()->set('security-headers.hsts_enabled', false);

    expect(securityHeadersRun()->headers->get('Strict-Transport-Security'))->toBeNull();
});

it('1PGM4-FR-SEC6: HSTS is sent with max-age and includeSubDomains when enabled', function () {
    config()->set('security-headers.hsts_enabled', true);
    config()->set('security-headers.hsts_max_age', 31536000);
    config()->set('security-headers.hsts_include_subdomains', true);
    config()->set('security-headers.hsts_preload', false);

    $value = securityHeadersRun()->headers->get('Strict-Transport-Security');

    expect($value)->toBe('max-age=31536000; includeSubDomains');
});

it('1PGM4-FR-SEC6: HSTS preload directive is added when configured', function () {
    config()->set('security-headers.hsts_enabled', true);
    config()->set('security-headers.hsts_include_subdomains', true);
    config()->set('security-headers.hsts_preload', true);

    expect(securityHeadersRun()->headers->get('Strict-Transport-Security'))->toContain('; preload');
});

it('1PGM4-FR-SEC1: CSP header is skipped when csp_enabled is false', function () {
    config()->set('security-headers.csp_enabled', false);

    expect(securityHeadersRun()->headers->get('Content-Security-Policy'))->toBeNull();
});

it('1PGM4-FR-SEC10/NFR-SEC3: Vite dev URL is injected only while the hot file exists', function () {
    $hot = public_path('hot');
    File::put($hot, 'http://localhost:5173');

    try {
        $csp = securityHeadersRun()->headers->get('Content-Security-Policy');

        expect($csp)->toContain('http://localhost:5173');
    } finally {
        File::delete($hot);
    }

    expect(securityHeadersRun()->headers->get('Content-Security-Policy'))->not->toContain('http://localhost:5173');
});

it('1PGM4-FR-SEC12: header values come from the security-headers config', function () {
    config()->set('security-headers.headers', [
        'X-Custom-Test-Header' => 'configured-value',
    ]);

    expect(securityHeadersRun()->headers->get('X-Custom-Test-Header'))->toBe('configured-value');
});
