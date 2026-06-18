<?php

declare(strict_types=1);

use App\Auth\AccessTokens\Models\AccessToken;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('can generate activation token for user', function () {
    $result = AccessToken::generateFor($this->user, 'activation');

    expect($result)->toHaveKeys(['token', 'plain_text']);
    expect($result['token'])->toBeInstanceOf(AccessToken::class);
    expect($result['token']->user_id)->toBe($this->user->id);
    expect($result['token']->token_type)->toBe('activation');
    expect($result['token']->attempts)->toBe(0);
});

test('can generate recovery token for user', function () {
    $result = AccessToken::generateFor($this->user, 'recovery');

    expect($result['token']->token_type)->toBe('recovery');
    expect($result['token']->expires_at)->not->toBeNull();
});

test('generated token has hashed token stored', function () {
    $result = AccessToken::generateFor($this->user, 'activation');

    expect(Hash::check($result['plain_text'], $result['token']->token))->toBeTrue();
});

test('generated token expires after configured TTL', function () {
    $result = AccessToken::generateFor($this->user, 'activation', ['ttl_days' => 7]);

    expect($result['token']->expires_at->diffInDays(now()))->toBeGreaterThanOrEqual(6);
});

test('verify returns true for valid token', function () {
    $result = AccessToken::generateFor($this->user, 'activation');

    $verified = AccessToken::verify($this->user, 'activation', $result['plain_text']);

    expect($verified)->toBeTrue();
});

test('verify returns false for invalid plain text', function () {
    AccessToken::generateFor($this->user, 'activation');

    $verified = AccessToken::verify($this->user, 'activation', 'wrong-plain-text');

    expect($verified)->toBeFalse();
});

test('verify returns false when no token exists', function () {
    $verified = AccessToken::verify($this->user, 'activation', 'some-text');

    expect($verified)->toBeFalse();
});

test('verify returns false for expired token', function () {
    $result = AccessToken::generateFor($this->user, 'activation', ['ttl_days' => 0]);
    $result['token']->update(['expires_at' => now()->subDay()]);

    $verified = AccessToken::verify($this->user, 'activation', $result['plain_text']);

    expect($verified)->toBeFalse();
});

test('verify increments attempts on failure', function () {
    $result = AccessToken::generateFor($this->user, 'activation');
    expect($result['token']->attempts)->toBe(0);

    AccessToken::verify($this->user, 'activation', 'wrong-text');

    $token = $result['token']->fresh();
    expect($token->attempts)->toBe(1);
    expect($token->last_attempt_at)->not->toBeNull();
});

test('verify resets attempts on success', function () {
    $result = AccessToken::generateFor($this->user, 'activation');
    $result['token']->update(['attempts' => 3]);

    AccessToken::verify($this->user, 'activation', $result['plain_text']);

    $token = $result['token']->fresh();
    expect($token->attempts)->toBe(0);
    expect($token->last_used_at)->not->toBeNull();
});

test('is expired returns true for past expiry', function () {
    $token = AccessToken::factory()->create([
        'user_id' => $this->user->id,
        'expires_at' => now()->subDay(),
    ]);

    expect($token->asAccessTokenState()->isExpired())->toBeTrue();
});

test('is expired returns false for future expiry', function () {
    $token = AccessToken::factory()->create([
        'user_id' => $this->user->id,
        'expires_at' => now()->addDays(30),
    ]);

    expect($token->asAccessTokenState()->isExpired())->toBeFalse();
});

test('is expired returns false when expires at is null', function () {
    $token = AccessToken::factory()->create([
        'user_id' => $this->user->id,
        'expires_at' => null,
    ]);

    expect($token->asAccessTokenState()->isExpired())->toBeFalse();
});

test('is revoked returns true when revoked at is set', function () {
    $token = AccessToken::factory()->create([
        'user_id' => $this->user->id,
        'revoked_at' => now(),
    ]);

    expect($token->asAccessTokenState()->isRevoked())->toBeTrue();
});

test('is valid returns true when not revoked and not expired', function () {
    $token = AccessToken::factory()->create([
        'user_id' => $this->user->id,
        'expires_at' => now()->addDays(30),
        'revoked_at' => null,
    ]);

    expect($token->asAccessTokenState()->isValid())->toBeTrue();
});

test('is valid returns false when revoked', function () {
    $token = AccessToken::factory()->create([
        'user_id' => $this->user->id,
        'revoked_at' => now(),
        'expires_at' => now()->addDays(30),
    ]);

    expect($token->asAccessTokenState()->isValid())->toBeFalse();
});

test('is valid returns false when expired', function () {
    $token = AccessToken::factory()->create([
        'user_id' => $this->user->id,
        'expires_at' => now()->subDay(),
        'revoked_at' => null,
    ]);

    expect($token->asAccessTokenState()->isValid())->toBeFalse();
});

test('revoke for marks tokens as revoked', function () {
    AccessToken::generateFor($this->user, 'activation');

    AccessToken::revokeFor($this->user, 'activation');

    $token = AccessToken::where('user_id', $this->user->id)->first();
    expect($token->revoked_at)->not->toBeNull();
});

test('revoke all expired revokes only expired tokens', function () {
    AccessToken::factory()->create([
        'user_id' => $this->user->id,
        'expires_at' => now()->subDay(),
        'revoked_at' => null,
    ]);
    AccessToken::factory()->create([
        'user_id' => $this->user->id,
        'expires_at' => now()->addDays(30),
        'revoked_at' => null,
    ]);

    $count = AccessToken::revokeAllExpired();

    expect($count)->toBe(1);
});

test('belongs to user', function () {
    $token = AccessToken::factory()->create([
        'user_id' => $this->user->id,
    ]);

    expect($token->user)->toBeInstanceOf(User::class);
    expect($token->user->id)->toBe($this->user->id);
});

test('update or create reuses existing token type', function () {
    $first = AccessToken::generateFor($this->user, 'activation');
    $firstPlain = $first['plain_text'];

    $second = AccessToken::generateFor($this->user, 'activation');

    expect($second['token']->id)->toBe($first['token']->id);
    expect($second['plain_text'])->not->toBe($firstPlain);
});

test('user relation returns null when user deleted', function () {
    $token = AccessToken::factory()->create([
        'user_id' => $this->user->id,
    ]);

    $this->user->delete();

    expect($token->fresh())->toBeNull();
});
