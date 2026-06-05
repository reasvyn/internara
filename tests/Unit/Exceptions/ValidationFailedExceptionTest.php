<?php

declare(strict_types=1);

use App\Exceptions\ValidationFailedException;

test('validation failed exception defaults', function () {
    $e = new ValidationFailedException;

    expect($e->getMessage())->toBe('Validation failed');
    expect($e->getHint())->toBe('Please check your input and try again.');
    expect($e->getContext())->toBe([]);
    expect($e->shouldReport())->toBeTrue();
});

test('validation failed exception accepts custom values', function () {
    $e = new ValidationFailedException(
        message: 'Invalid email format',
        hint: 'Enter a valid email address',
        context: ['field' => 'email'],
    );

    expect($e->getMessage())->toBe('Invalid email format');
    expect($e->getHint())->toBe('Enter a valid email address');
    expect($e->getContext())->toBe(['field' => 'email']);
});
