<?php

declare(strict_types=1);

use App\Auth\AccessTokens\Entities\ActivationToken;
use Carbon\Carbon;

test('activation token can be created with constructor', function () {
    $expiresAt = Carbon::now()->addDays(30);
    $token = new ActivationToken(
        plainText: 'raw-token-string',
        tokenId: 'uuid-123',
        expiresAt: $expiresAt,
    );

    expect($token->plainText())->toBe('raw-token-string');
    expect($token->tokenId())->toBe('uuid-123');
    expect($token->expiresAt())->toBe($expiresAt);
});

test('activation token is immutable', function () {
    $token = new ActivationToken('p', 'id', Carbon::now()->addDay());

    $reflection = new ReflectionClass($token);
    foreach ($reflection->getProperties() as $prop) {
        expect($prop->isReadOnly())->toBeTrue();
    }
});

test('activation token exposes properties via accessors', function () {
    $expiresAt = Carbon::parse('2026-07-01 12:00:00');
    $token = new ActivationToken('raw-text', 'token-id-1', $expiresAt);

    expect($token->plainText())->toBe('raw-text');
    expect($token->tokenId())->toBe('token-id-1');
    expect($token->expiresAt())->toBe($expiresAt);
});
