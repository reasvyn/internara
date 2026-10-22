<?php

declare(strict_types=1);

namespace Tests\Feature\User;

use App\Modules\User\Models\User;
use App\Modules\User\Services\UserIdentifierGenerator;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use RuntimeException;

uses(LazilyRefreshDatabase::class);

describe('95EVB: User Identifier Generator Service', function (): void {
    test('95EVB-FR-USER-048: generates alphanumeric lowercase username from email local-part', function (): void {
        $username = UserIdentifierGenerator::generateUsername('John.Doe_99@example.com');

        expect($username)->toBe('johndoe99');
    });

    test('95EVB-FR-USER-048: falls back to user when email local-part has no alphanumeric characters', function (): void {
        $username = UserIdentifierGenerator::generateUsername('..._---@example.com');

        expect($username)->toBe('user');
    });

    test('95EVB-FR-USER-048: increments numeric suffix when collision occurs', function (): void {
        User::factory()->create(['username' => 'alice']);

        $firstCollision = UserIdentifierGenerator::generateUsername('alice@example.com');
        expect($firstCollision)->toBe('alice1');

        User::factory()->create(['username' => 'alice1']);

        $secondCollision = UserIdentifierGenerator::generateUsername('alice@example.com');
        expect($secondCollision)->toBe('alice2');
    });

    test('95EVB-FR-USER-048: throws RuntimeException when maximum collision attempts are reached', function (): void {
        // Create base username
        User::factory()->create(['username' => 'clash']);

        // Mock User query to always report existence for 'clash'
        // Or create a mock to simulate 100 collision hits
        for ($i = 1; $i <= 100; $i++) {
            User::factory()->create(['username' => 'clash'.$i]);
        }

        expect(fn () => UserIdentifierGenerator::generateUsername('clash@example.com'))
            ->toThrow(RuntimeException::class, 'Unable to generate unique username after 100 attempts.');
    });
});
