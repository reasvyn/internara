<?php

declare(strict_types=1);

use App\Core\Exceptions\ConflictException;

test('conflict exception defaults', function () {
    $e = new ConflictException;

    expect($e->getMessage())->toBe('Conflict');
    expect($e->getHint())->toBe('The request conflicts with the current state of the resource.');
    expect($e->getContext())->toBe([]);
    expect($e->shouldReport())->toBeTrue();
});

test('conflict exception accepts custom values', function () {
    $e = new ConflictException(
        message: 'Duplicate entry',
        hint: 'This record already exists',
        context: ['record_id' => 99],
    );

    expect($e->getMessage())->toBe('Duplicate entry');
    expect($e->getHint())->toBe('This record already exists');
    expect($e->getContext())->toBe(['record_id' => 99]);
});
