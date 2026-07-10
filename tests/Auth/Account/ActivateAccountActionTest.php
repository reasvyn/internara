<?php

declare(strict_types=1);

use App\Auth\AccessTokens\Models\AccessToken;
use App\Auth\Account\Actions\ActivateAccountAction;
use App\Core\Exceptions\RejectedException;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(LazilyRefreshDatabase::class);

describe('ActivateAccountAction', function () {
    test('activates account with valid code and hashed password', function () {
        $user = User::factory()->create();
        $password = 'secure-password-123';
        $token = AccessToken::generateFor($user, 'activation', ['name' => 'Account Activation']);

        $result = app(ActivateAccountAction::class)->execute(
            $user,
            $token['plain_text'],
            $password,
        );

        expect($result->id)->toBe($user->id);
        expect(Hash::check($password, $result->password))->toBeTrue();
    });

    test('throws exception with invalid code', function () {
        $user = User::factory()->create();

        app(ActivateAccountAction::class)->execute($user, 'invalid-code-12345', 'new-password');
    })->throws(RejectedException::class);

    test('revokes token after successful activation', function () {
        $user = User::factory()->create();
        $token = AccessToken::generateFor($user, 'activation', ['name' => 'Account Activation']);

        app(ActivateAccountAction::class)->execute($user, $token['plain_text'], 'new-password');

        $stored = AccessToken::where('user_id', $user->id)
            ->where('token_type', 'activation')
            ->first();
        expect($stored->revoked_at)->not->toBeNull();
    });
});
