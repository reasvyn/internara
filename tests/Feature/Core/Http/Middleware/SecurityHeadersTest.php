<?php

declare(strict_types=1);

namespace Tests\Feature\Core\Http\Middleware;

use App\Core\Http\Middleware\SecurityHeaders;
use Illuminate\Support\Facades\Route;

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
