<?php

declare(strict_types=1);

use App\Core\Exceptions\NotFoundException;

test('not found exception defaults', function () {
    $e = new NotFoundException;

    expect($e->getMessage())->toBe('Resource not found');
    expect($e->getHint())->toBe('The requested resource does not exist or has been removed.');
    expect($e->getContext())->toBe([]);
    expect($e->isUserFacing())->toBeTrue();
});

test('not found exception accepts custom message hint and context', function () {
    $e = new NotFoundException(
        message: 'User not found',
        hint: 'Check the user ID',
        context: ['user_id' => 42],
    );

    expect($e->getMessage())->toBe('User not found');
    expect($e->getHint())->toBe('Check the user ID');
    expect($e->getContext())->toBe(['user_id' => 42]);
});
