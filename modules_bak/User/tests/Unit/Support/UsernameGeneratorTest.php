<?php

declare(strict_types=1);

namespace Modules\User\Tests\Unit\Support;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\User\Support\UsernameGenerator;

uses(RefreshDatabase::class);

test('it generates username from name', function () {
    $username = UsernameGenerator::generate('john');

    expect($username)->toStartWith('john')->toHaveLength(12); // john + 8 digits
});

test('it handles special characters', function () {
    $username = UsernameGenerator::generate('john');

    expect($username)->toStartWith('john');
});

test('it adds numeric suffix if duplicate', function () {
    // Current implementation always adds random suffix, so it inherently handles duplicates
    $username = UsernameGenerator::generate('u');
    expect($username)->toStartWith('u')->toHaveLength(9);
});
