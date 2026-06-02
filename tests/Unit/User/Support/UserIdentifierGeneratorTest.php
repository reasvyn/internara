<?php

declare(strict_types=1);

use App\Domain\User\Support\UserIdentifierGenerator;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

describe('UserIdentifierGenerator', function () {
    it('generates a username starting with u', function () {
        $username = UserIdentifierGenerator::generateUsername();

        expect($username)->toStartWith('u');
    });

    it('generates username of correct length', function () {
        $username = UserIdentifierGenerator::generateUsername(8);

        expect(strlen($username))->toBe(9);
    });

    it('generates username with custom length', function () {
        $username = UserIdentifierGenerator::generateUsername(12);

        expect(strlen($username))->toBe(13);
    });

    it('generates unique usernames', function () {
        $usernames = [];
        $count = 10;

        for ($i = 0; $i < $count; $i++) {
            $usernames[] = UserIdentifierGenerator::generateUsername();
        }

        expect(array_unique($usernames))->toHaveCount($count);
    });

    it('generates lowercase usernames', function () {
        $username = UserIdentifierGenerator::generateUsername();

        expect($username)->toBe(strtolower($username));
    });
});
