<?php

declare(strict_types=1);

use App\Core\Exceptions\RejectedException;
use App\Core\Exceptions\UnauthorizedException;
use App\Core\Exceptions\ValidationFailedException;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    Route::get('/_test_exception/{type}', function (string $type) {
        throw match ($type) {
            'unauthorized' => new UnauthorizedException,
            'validation' => new ValidationFailedException,
            'rejected' => new RejectedException('Business rule violated'),
            default => new RuntimeException('Unexpected'),
        };
    })->name('_test_exception');
});

test('unauthorized exception returns 403', function () {
    $response = $this->get('/_test_exception/unauthorized');

    $response->assertStatus(403);
});

test('validation failed exception returns 422', function () {
    $response = $this->get('/_test_exception/validation');

    $response->assertStatus(422);
});

test('rejected exception returns 400', function () {
    $response = $this->get('/_test_exception/rejected');

    $response->assertStatus(400);
});

test('unauthorized exception returns json with message when request expects json', function () {
    $response = $this->getJson('/_test_exception/unauthorized');

    $response->assertStatus(403);
    $response->assertJson(['message' => 'Unauthorized']);
});

test('rejected exception returns json with message when request expects json', function () {
    $response = $this->getJson('/_test_exception/rejected');

    $response->assertStatus(400);
    $response->assertJson(['message' => 'Business rule violated']);
});
