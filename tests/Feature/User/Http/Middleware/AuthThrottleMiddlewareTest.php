<?php

declare(strict_types=1);

use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    Route::post('/_test_throttle', function () {
        return 'ok';
    })->middleware('auth.throttle');
});

test('allows get requests without hitting rate limiter', function () {
    Route::get('/_test_throttle_get', function () {
        return 'ok';
    })->middleware('auth.throttle');

    $response = $this->get('/_test_throttle_get');

    $response->assertStatus(200);
});

test('hits rate limiter on non-get requests', function () {
    $response = $this->post('/_test_throttle');

    $response->assertStatus(200);

    expect(RateLimiter::tooManyAttempts('auth-throttle:127.0.0.1', 30))->toBeFalse();
});

test('returns 302 redirect when rate limit exceeded', function () {
    for ($i = 0; $i < 30; $i++) {
        $this->post('/_test_throttle');
    }

    $response = $this->post('/_test_throttle');

    $response->assertRedirect(route('login'));
});

test('returns json error when rate limited and expects json', function () {
    for ($i = 0; $i < 30; $i++) {
        $this->post('/_test_throttle');
    }

    $response = $this->postJson('/_test_throttle');

    $response->assertStatus(429);
    $response->assertJsonStructure(['message']);
});
