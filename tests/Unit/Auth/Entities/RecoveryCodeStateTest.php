<?php

declare(strict_types=1);

use App\Auth\AccountRecovery\Entities\RecoveryCodeState;

test('recovery code is valid when not used and not expired', function () {
    $entity = new RecoveryCodeState(usedAt: null, expiresAt: now()->addHour());

    expect($entity->isValid())->toBeTrue();
});

test('recovery code is not valid when already used', function () {
    $entity = new RecoveryCodeState(usedAt: now()->subDay(), expiresAt: now()->addHour());

    expect($entity->isValid())->toBeFalse();
});

test('recovery code is not valid when expired', function () {
    $entity = new RecoveryCodeState(usedAt: null, expiresAt: now()->subHour());

    expect($entity->isValid())->toBeFalse();
});

test('recovery code is valid when expiresAt is null', function () {
    $entity = new RecoveryCodeState(usedAt: null, expiresAt: null);

    expect($entity->isValid())->toBeTrue();
});
