<?php

declare(strict_types=1);

namespace Tests\Core\Http\Middleware;

use App\Core\Http\Middleware\SecurityHeaders;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Mockery;

beforeEach(function () {
    config([
        'security-headers.headers' => [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
        ],
        'security-headers.csp_enabled' => true,
        'security-headers.csp' => "default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self'; font-src 'self'; connect-src 'self';",
    ]);
});

test('security headers middleware applies configured headers', function () {
    Route::get('/_test_security_headers', function () {
        return 'ok';
    })->middleware(SecurityHeaders::class);

    $response = $this->get('/_test_security_headers');

    $response->assertStatus(200);
    $response->assertHeader('X-Content-Type-Options', 'nosniff');
    $response->assertHeader('X-Frame-Options', 'DENY');
    $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    $response->assertHeader('Content-Security-Policy');
});

test('security headers middleware respects csp_enabled flag', function () {
    config(['security-headers.csp_enabled' => false]);

    Route::get('/_test_security_headers_no_csp', function () {
        return 'ok';
    })->middleware(SecurityHeaders::class);

    $response = $this->get('/_test_security_headers_no_csp');

    $response->assertStatus(200);
    expect($response->headers->has('Content-Security-Policy'))->toBeFalse();
});

test('security headers injects vite dev url when hot file exists', function () {
    File::shouldReceive('exists')
        ->with(Mockery::on(fn ($path) => str_ends_with($path, '/hot')))
        ->once()
        ->andReturnTrue();

    File::shouldReceive('get')
        ->with(Mockery::on(fn ($path) => str_ends_with($path, '/hot')))
        ->once()
        ->andReturn('http://localhost:5173');

    Route::get('/_test_vite_headers', function () {
        return 'ok';
    })->middleware(SecurityHeaders::class);

    $response = $this->get('/_test_vite_headers');

    $response->assertStatus(200);
    $csp = $response->headers->get('Content-Security-Policy');

    expect($csp)->toContain('http://localhost:5173');
});

test('security headers does not inject vite url when hot file is empty', function () {
    File::shouldReceive('exists')
        ->with(Mockery::on(fn ($path) => str_ends_with($path, '/hot')))
        ->once()
        ->andReturnTrue();

    File::shouldReceive('get')
        ->with(Mockery::on(fn ($path) => str_ends_with($path, '/hot')))
        ->once()
        ->andReturn('');

    Route::get('/_test_empty_hot_headers', function () {
        return 'ok';
    })->middleware(SecurityHeaders::class);

    $response = $this->get('/_test_empty_hot_headers');

    $csp = $response->headers->get('Content-Security-Policy');

    expect($csp)->not->toContain('localhost');
});

test('security headers does not inject vite url when hot file missing', function () {
    File::shouldReceive('exists')
        ->with(Mockery::on(fn ($path) => str_ends_with($path, '/hot')))
        ->once()
        ->andReturnFalse();

    Route::get('/_test_no_hot_headers', function () {
        return 'ok';
    })->middleware(SecurityHeaders::class);

    $response = $this->get('/_test_no_hot_headers');

    $csp = $response->headers->get('Content-Security-Policy');

    expect($csp)->not->toContain('localhost');
});

test('security headers csp contains default-src self', function () {
    Route::get('/_test_csp_default', function () {
        return 'ok';
    })->middleware(SecurityHeaders::class);

    $response = $this->get('/_test_csp_default');

    $csp = $response->headers->get('Content-Security-Policy');
    expect($csp)->toContain("default-src 'self'");
});

test('hsts header is not sent by default', function () {
    Route::get('/_test_hsts_default', function () {
        return 'ok';
    })->middleware(SecurityHeaders::class);

    $response = $this->get('/_test_hsts_default');

    expect($response->headers->has('Strict-Transport-Security'))->toBeFalse();
});

test('hsts header is sent when enabled', function () {
    config([
        'security-headers.hsts_enabled' => true,
        'security-headers.hsts_max_age' => 31536000,
        'security-headers.hsts_include_subdomains' => true,
        'security-headers.hsts_preload' => false,
    ]);

    Route::get('/_test_hsts_enabled', function () {
        return 'ok';
    })->middleware(SecurityHeaders::class);

    $response = $this->get('/_test_hsts_enabled');

    $response->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
});

test('hsts header includes preload when enabled', function () {
    config([
        'security-headers.hsts_enabled' => true,
        'security-headers.hsts_max_age' => 31536000,
        'security-headers.hsts_include_subdomains' => false,
        'security-headers.hsts_preload' => true,
    ]);

    Route::get('/_test_hsts_preload', function () {
        return 'ok';
    })->middleware(SecurityHeaders::class);

    $response = $this->get('/_test_hsts_preload');

    $response->assertHeader('Strict-Transport-Security', 'max-age=31536000; preload');
});

test('hsts header without subdomains or preload', function () {
    config([
        'security-headers.hsts_enabled' => true,
        'security-headers.hsts_max_age' => 86400,
        'security-headers.hsts_include_subdomains' => false,
        'security-headers.hsts_preload' => false,
    ]);

    Route::get('/_test_hsts_minimal', function () {
        return 'ok';
    })->middleware(SecurityHeaders::class);

    $response = $this->get('/_test_hsts_minimal');

    $response->assertHeader('Strict-Transport-Security', 'max-age=86400');
});
