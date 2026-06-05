<?php

declare(strict_types=1);

use App\Exceptions\RateLimitException;

test('rate limit exception defaults', function () {
    $e = new RateLimitException;

    expect($e->getMessage())->toBe('Too many requests');
    expect($e->getHint())->toBe('Please wait before making another request.');
    expect($e->getContext())->toBe([]);
    expect($e->shouldReport())->toBeTrue();
});

test('rate limit exception accepts custom values', function () {
    $e = new RateLimitException(
        message: 'Slow down',
        hint: 'Try again in 30 seconds',
        context: ['retry_after' => 30],
    );

    expect($e->getMessage())->toBe('Slow down');
    expect($e->getHint())->toBe('Try again in 30 seconds');
    expect($e->getContext())->toBe(['retry_after' => 30]);
});
