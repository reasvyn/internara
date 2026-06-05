<?php

declare(strict_types=1);

namespace Tests\Unit\User;

use App\User\Models\User;
use App\User\Support\UserIdentifierGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('it generates lowercase alphanumeric username from email', function () {
    $username = UserIdentifierGenerator::generateUsername('John.Doe_123@example.com');
    expect($username)->toBe('johndoe123');
});

test('it defaults empty base name to user', function () {
    $username = UserIdentifierGenerator::generateUsername('._@example.com');
    expect($username)->toBe('user');
});

test('it increments username if collision occurs', function () {
    // Create first user
    User::factory()->create([
        'username' => 'usertest',
        'email' => 'usertest@example.com',
    ]);

    // Generate for next user with same base name
    $username = UserIdentifierGenerator::generateUsername('usertest@example.com');
    expect($username)->toBe('usertest1');

    // Create second user
    User::factory()->create([
        'username' => 'usertest1',
        'email' => 'usertest2@example.com',
    ]);

    // Generate for third user with same base name
    $username = UserIdentifierGenerator::generateUsername('usertest@example.com');
    expect($username)->toBe('usertest2');
});
