<?php

declare(strict_types=1);

use App\Core\Exceptions\UnauthorizedException;

test('unauthorized exception defaults', function () {
    $e = new UnauthorizedException;

    expect($e->getMessage())->toBe('Unauthorized');
    expect($e->getHint())->toBe('You do not have permission to perform this action.');
    expect($e->getContext())->toBe([]);
    expect($e->isUserFacing())->toBeTrue();
});

test('unauthorized exception accepts custom values', function () {
    $e = new UnauthorizedException(
        message: 'Admin only',
        hint: 'Log in as an administrator',
        context: ['required_role' => 'admin'],
    );

    expect($e->getMessage())->toBe('Admin only');
    expect($e->getHint())->toBe('Log in as an administrator');
    expect($e->getContext())->toBe(['required_role' => 'admin']);
});
