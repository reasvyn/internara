<?php

declare(strict_types=1);

use App\Auth\Account\Actions\ActivateAccountAction;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(LazilyRefreshDatabase::class);

describe('ActivateAccountAction', function () {
    test('activates account with hashed password', function () {
        $user = User::factory()->create();
        $password = 'secure-password-123';

        $result = app(ActivateAccountAction::class)->execute($user, $password);

        expect($result->id)->toBe($user->id);
        expect(Hash::check($password, $result->password))->toBeTrue();
    });

    test('returns the same user instance', function () {
        $user = User::factory()->create();

        $result = app(ActivateAccountAction::class)->execute($user, 'new-password');

        expect($result->id)->toBe($user->id);
    });
});
