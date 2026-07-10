<?php

declare(strict_types=1);

use App\Auth\Account\Entities\AccountActivation;
use Carbon\Carbon;

test('account activation detects requires activation', function () {
    $activated = new AccountActivation(
        isActivated: true,
        tokenExpiresAt: null,
        tokenIsValid: false,
        attempts: 0,
    );
    expect($activated->requiresActivation())->toBeFalse();

    $notActivated = new AccountActivation(
        isActivated: false,
        tokenExpiresAt: Carbon::now()->addDay(),
        tokenIsValid: true,
        attempts: 1,
    );
    expect($notActivated->requiresActivation())->toBeTrue();
});

test('account activation detects token validity', function () {
    $valid = new AccountActivation(
        isActivated: false,
        tokenExpiresAt: Carbon::now()->addDay(),
        tokenIsValid: true,
        attempts: 0,
    );
    expect($valid->isTokenValid())->toBeTrue();
    expect($valid->isTokenExpired())->toBeFalse();

    $invalid = new AccountActivation(
        isActivated: false,
        tokenExpiresAt: Carbon::now()->subDay(),
        tokenIsValid: false,
        attempts: 3,
    );
    expect($invalid->isTokenValid())->toBeFalse();
    expect($invalid->isTokenExpired())->toBeTrue();
});

test('account activation returns token expires at', function () {
    $date = Carbon::now()->addDay();
    $entity = new AccountActivation(
        isActivated: false,
        tokenExpiresAt: $date,
        tokenIsValid: true,
        attempts: 0,
    );
    expect($entity->tokenExpiresAt())->toBe($date);

    $noToken = new AccountActivation(
        isActivated: true,
        tokenExpiresAt: null,
        tokenIsValid: false,
        attempts: 0,
    );
    expect($noToken->tokenExpiresAt())->toBeNull();
});

test('account activation is token expired returns false when already activated', function () {
    $activated = new AccountActivation(
        isActivated: true,
        tokenExpiresAt: null,
        tokenIsValid: false,
        attempts: 0,
    );
    expect($activated->isTokenExpired())->toBeFalse();
});

test('account activation detects max attempts', function () {
    $under = new AccountActivation(
        isActivated: false,
        tokenExpiresAt: Carbon::now()->addDay(),
        tokenIsValid: true,
        attempts: 3,
    );
    expect($under->hasExceededMaxAttempts())->toBeFalse();
    expect($under->hasExceededMaxAttempts(3))->toBeTrue();

    $over = new AccountActivation(
        isActivated: false,
        tokenExpiresAt: Carbon::now()->addDay(),
        tokenIsValid: true,
        attempts: 5,
    );
    expect($over->hasExceededMaxAttempts())->toBeTrue();
});

test('account activation returns attempts count', function () {
    $entity = new AccountActivation(
        isActivated: false,
        tokenExpiresAt: Carbon::now()->addDay(),
        tokenIsValid: true,
        attempts: 7,
    );
    expect($entity->attempts())->toBe(7);
});
