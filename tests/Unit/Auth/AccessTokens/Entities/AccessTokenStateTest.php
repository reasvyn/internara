<?php

declare(strict_types=1);

use App\Auth\AccessTokens\Entities\AccessTokenState;
use Carbon\Carbon;

test('access token state detects expired tokens', function () {
    $expired = new AccessTokenState(
        expiresAt: Carbon::now()->subDay(),
        revokedAt: null,
        attempts: 0,
    );
    expect($expired->isExpired())->toBeTrue();
    expect($expired->isValid())->toBeFalse();

    $future = new AccessTokenState(
        expiresAt: Carbon::now()->addDay(),
        revokedAt: null,
        attempts: 0,
    );
    expect($future->isExpired())->toBeFalse();
    expect($future->isValid())->toBeTrue();

    $noExpiry = new AccessTokenState(
        expiresAt: null,
        revokedAt: null,
        attempts: 0,
    );
    expect($noExpiry->isExpired())->toBeFalse();
});

test('access token state detects revoked tokens', function () {
    $revoked = new AccessTokenState(
        expiresAt: Carbon::now()->addDay(),
        revokedAt: Carbon::now(),
        attempts: 0,
    );
    expect($revoked->isRevoked())->toBeTrue();
    expect($revoked->isValid())->toBeFalse();

    $notRevoked = new AccessTokenState(
        expiresAt: Carbon::now()->addDay(),
        revokedAt: null,
        attempts: 0,
    );
    expect($notRevoked->isRevoked())->toBeFalse();
});

test('access token state validates max attempts', function () {
    $underLimit = new AccessTokenState(
        expiresAt: null,
        revokedAt: null,
        attempts: 3,
    );
    expect($underLimit->hasExceededMaxAttempts())->toBeFalse();
    expect($underLimit->hasExceededMaxAttempts(5))->toBeFalse();
    expect($underLimit->hasExceededMaxAttempts(3))->toBeTrue();

    $atLimit = new AccessTokenState(
        expiresAt: null,
        revokedAt: null,
        attempts: 5,
    );
    expect($atLimit->hasExceededMaxAttempts())->toBeTrue();

    $overLimit = new AccessTokenState(
        expiresAt: null,
        revokedAt: null,
        attempts: 10,
    );
    expect($overLimit->hasExceededMaxAttempts())->toBeTrue();
});
