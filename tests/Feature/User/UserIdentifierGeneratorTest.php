<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

use App\Domain\User\Support\UserIdentifierGenerator;

describe('UserIdentifierGenerator', function () {
    it('generates a username starting with u', function () {
        $username = UserIdentifierGenerator::generateUsername();

        expect($username)->toStartWith('u');
    });

    it('generates username of expected length', function () {
        $username = UserIdentifierGenerator::generateUsername(10);

        expect(strlen($username))->toBe(11); // 'u' + 10 chars
    });

    it('is a final class', function () {
        $ref = new ReflectionClass(UserIdentifierGenerator::class);

        expect($ref->isFinal())->toBeTrue();
    });
});
