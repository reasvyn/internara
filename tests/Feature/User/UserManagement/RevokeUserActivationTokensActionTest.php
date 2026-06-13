<?php

declare(strict_types=1);

use App\Auth\ApiTokens\Models\ApiToken;
use App\User\Models\User;
use App\User\UserManagement\Actions\RevokeUserActivationTokensAction;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('revokes activation tokens for user', function () {
    $user = User::factory()->create();
    $token = ApiToken::factory()->create([
        'user_id' => $user->id,
        'token_type' => 'activation',
        'revoked_at' => null,
    ]);

    $action = app(RevokeUserActivationTokensAction::class);
    $action->execute($user);

    expect($token->fresh()->revoked_at)->not->toBeNull();
});

test('does not fail when user has no activation tokens', function () {
    $user = User::factory()->create();

    $action = app(RevokeUserActivationTokensAction::class);
    $action->execute($user);

    expect(true)->toBeTrue();
});

test('only revokes activation type tokens', function () {
    $user = User::factory()->create();
    $activation = ApiToken::factory()->create([
        'user_id' => $user->id,
        'token_type' => 'activation',
        'revoked_at' => null,
    ]);
    $other = ApiToken::factory()->create([
        'user_id' => $user->id,
        'token_type' => 'api',
        'revoked_at' => null,
    ]);

    $action = app(RevokeUserActivationTokensAction::class);
    $action->execute($user);

    expect($activation->fresh()->revoked_at)->not->toBeNull();
    expect($other->fresh()->revoked_at)->toBeNull();
});
