<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

test('2CF4Y-FR-MW10: registers admin and global named route limiters', function () {
    expect(is_callable(RateLimiter::limiter('admin')))->toBeTrue();
    expect(is_callable(RateLimiter::limiter('global')))->toBeTrue();
});

test('2CF4Y-FR-MW10: applies canonical limits for admin and global route groups', function () {
    $request = Request::create('/dashboard');
    $request->server->set('REMOTE_ADDR', '127.0.0.1');

    $adminLimiter = RateLimiter::limiter('admin');
    $globalLimiter = RateLimiter::limiter('global');

    expect($adminLimiter($request)->maxAttempts)->toBe(60);
    expect($globalLimiter($request)->maxAttempts)->toBe(30);
});
