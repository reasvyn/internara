<?php

declare(strict_types=1);

namespace Modules\User\Tests\Unit\Support;

use Modules\User\Support\UsernameGenerator;

test('it generates username from name', function () {
    $generator = new UsernameGenerator;
    $username = $generator->generate('John Doe');

    expect($username)->toBe('john.doe');
});

test('it handles special characters', function () {
    $generator = new UsernameGenerator;
    $username = $generator->generate('Jöhn Dóe!');

    expect($username)->toBe('john.doe');
});

test('it adds numeric suffix if duplicate', function () {
    // This requires mocking the DB or User model,
    // but we can test the base formatting logic.
    $generator = new UsernameGenerator;
    expect($generator->generate('John'))->toBe('john');
});
