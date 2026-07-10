<?php

declare(strict_types=1);

use App\User\Models\User;
use App\User\Services\UserIdentifierGenerator;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('generates username from email local part', function () {
    $username = UserIdentifierGenerator::generateUsername('john.doe@example.com');

    expect($username)->toBe('johndoe');
});

test('removes non-alphanumeric characters from email local part', function () {
    $username = UserIdentifierGenerator::generateUsername('john.doe+tag@example.com');

    expect($username)->toMatch('/^[a-z][a-z0-9]*$/');
    expect($username)->not->toContain('+');
    expect($username)->not->toContain('.');
});

test('generates username in lowercase', function () {
    $username = UserIdentifierGenerator::generateUsername('John.Doe@Example.com');

    expect($username)->toBe(strtolower($username));
});

test('falls back to user prefix when email local part is empty', function () {
    $username = UserIdentifierGenerator::generateUsername('@example.com');

    expect($username)->toMatch('/^user\d*$/');
});

test('falls back to user prefix when email local part is all special chars', function () {
    $username = UserIdentifierGenerator::generateUsername('!@#$%^@example.com');

    expect($username)->toMatch('/^user\d*$/');
});

test('appends incrementing number when username already exists', function () {
    User::factory()->create(['username' => 'johndoe']);

    $username = UserIdentifierGenerator::generateUsername('john.doe@example.com');

    expect($username)->toBe('johndoe1');
});

test('increments number further on subsequent collisions', function () {
    User::factory()->create(['username' => 'jane']);
    User::factory()->create(['username' => 'jane1']);
    User::factory()->create(['username' => 'jane2']);

    $username = UserIdentifierGenerator::generateUsername('jane@example.com');

    expect($username)->toBe('jane3');
});

test('throws runtime exception after maximum attempts', function () {
    for ($i = 0; $i < 100; $i++) {
        User::factory()->create(['username' => 'user'.($i === 0 ? '' : $i)]);
    }

    expect(fn () => UserIdentifierGenerator::generateUsername('@test.com'))->toThrow(
        RuntimeException::class,
        'Unable to generate unique username',
    );
});
