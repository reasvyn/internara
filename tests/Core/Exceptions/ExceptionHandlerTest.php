<?php

declare(strict_types=1);

use App\Core\Exceptions\ConflictException;
use App\Core\Exceptions\NotFoundException;
use App\Core\Exceptions\RateLimitException;
use App\Core\Exceptions\UnauthorizedException;
use App\Core\Exceptions\ValidationFailedException;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    Route::get('/_test_exception/{type}', function (string $type) {
        throw match ($type) {
            'not_found' => new NotFoundException,
            'unauthorized' => new UnauthorizedException,
            'validation' => new ValidationFailedException,
            'rate_limit' => new RateLimitException,
            'conflict' => new ConflictException,
            default => new RuntimeException('Unexpected'),
        };
    })->name('_test_exception');
});

test('not found exception returns 404', function () {
    $response = $this->get('/_test_exception/not_found');

    $response->assertStatus(404);
});

test('unauthorized exception returns 403', function () {
    $response = $this->get('/_test_exception/unauthorized');

    $response->assertStatus(403);
});

test('validation failed exception returns 422', function () {
    $response = $this->get('/_test_exception/validation');

    $response->assertStatus(422);
});

test('rate limit exception returns 429', function () {
    $response = $this->get('/_test_exception/rate_limit');

    $response->assertStatus(429);
});

test('app exception returns json with message when request expects json', function () {
    $response = $this->getJson('/_test_exception/not_found');

    $response->assertStatus(404);
    $response->assertJson(['message' => 'Resource not found']);
});

test('non-user-facing exception returns generic json message', function () {
    $response = $this->getJson('/_test_exception/rate_limit');

    $response->assertStatus(429);
    $response->assertJson(['message' => 'Kesalahan tidak diketahui.']);
});

test('user-facing action exception returns its message in json', function () {
    $response = $this->getJson('/_test_exception/conflict');

    $response->assertStatus(500);
    $response->assertJson(['message' => 'Conflict']);
});
