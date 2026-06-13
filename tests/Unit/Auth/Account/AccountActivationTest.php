<?php

declare(strict_types=1);

use App\Auth\Account\Entities\AccountActivation;
use Carbon\Carbon;

test('requires activation when token exists', function () {
    $entity = new AccountActivation(
        isActivated: false,
        tokenExpiresAt: Carbon::now()->addDays(30),
        tokenIsValid: true,
        attempts: 0,
    );

    expect($entity->requiresActivation())->toBeTrue();
});

test('does not require activation when already activated', function () {
    $entity = new AccountActivation(
        isActivated: true,
        tokenExpiresAt: null,
        tokenIsValid: false,
        attempts: 0,
    );

    expect($entity->requiresActivation())->toBeFalse();
});

test('is token valid returns true for valid token', function () {
    $entity = new AccountActivation(
        isActivated: false,
        tokenExpiresAt: Carbon::now()->addDays(30),
        tokenIsValid: true,
        attempts: 0,
    );

    expect($entity->isTokenValid())->toBeTrue();
});

test('is token valid returns false for expired token', function () {
    $entity = new AccountActivation(
        isActivated: false,
        tokenExpiresAt: Carbon::now()->subDay(),
        tokenIsValid: false,
        attempts: 2,
    );

    expect($entity->isTokenValid())->toBeFalse();
});

test('token is expired when invalid and not activated', function () {
    $entity = new AccountActivation(
        isActivated: false,
        tokenExpiresAt: Carbon::now()->subDay(),
        tokenIsValid: false,
        attempts: 0,
    );

    expect($entity->isTokenExpired())->toBeTrue();
});

test('token is not expired when already activated', function () {
    $entity = new AccountActivation(
        isActivated: true,
        tokenExpiresAt: Carbon::now()->subDay(),
        tokenIsValid: false,
        attempts: 0,
    );

    expect($entity->isTokenExpired())->toBeFalse();
});

test('token is not expired when still valid', function () {
    $entity = new AccountActivation(
        isActivated: false,
        tokenExpiresAt: Carbon::now()->addDay(),
        tokenIsValid: true,
        attempts: 0,
    );

    expect($entity->isTokenExpired())->toBeFalse();
});

test('returns token expiry', function () {
    $expiresAt = Carbon::now()->addDays(30);
    $entity = new AccountActivation(
        isActivated: false,
        tokenExpiresAt: $expiresAt,
        tokenIsValid: true,
        attempts: 0,
    );

    expect($entity->tokenExpiresAt())->toBe($expiresAt);
});

test('returns null token expiry when no token', function () {
    $entity = new AccountActivation(
        isActivated: true,
        tokenExpiresAt: null,
        tokenIsValid: false,
        attempts: 0,
    );

    expect($entity->tokenExpiresAt())->toBeNull();
});

test('returns attempt count', function () {
    $entity = new AccountActivation(
        isActivated: false,
        tokenExpiresAt: Carbon::now()->addDays(30),
        tokenIsValid: true,
        attempts: 3,
    );

    expect($entity->attempts())->toBe(3);
});

test('has not exceeded max attempts when under threshold', function () {
    $entity = new AccountActivation(
        isActivated: false,
        tokenExpiresAt: Carbon::now()->addDays(30),
        tokenIsValid: true,
        attempts: 3,
    );

    expect($entity->hasExceededMaxAttempts())->toBeFalse();
});

test('has exceeded max attempts at exactly threshold', function () {
    $entity = new AccountActivation(
        isActivated: false,
        tokenExpiresAt: Carbon::now()->addDays(30),
        tokenIsValid: true,
        attempts: 5,
    );

    expect($entity->hasExceededMaxAttempts())->toBeTrue();
});

test('has exceeded max attempts with custom threshold', function () {
    $entity = new AccountActivation(
        isActivated: false,
        tokenExpiresAt: Carbon::now()->addDays(30),
        tokenIsValid: true,
        attempts: 3,
    );

    expect($entity->hasExceededMaxAttempts(3))->toBeTrue();
    expect($entity->hasExceededMaxAttempts(10))->toBeFalse();
});

test('account activation is immutable', function () {
    $entity = new AccountActivation(
        isActivated: false,
        tokenExpiresAt: null,
        tokenIsValid: false,
        attempts: 0,
    );

    $reflection = new ReflectionClass($entity);
    foreach ($reflection->getProperties() as $prop) {
        expect($prop->isReadOnly())->toBeTrue();
    }
});
