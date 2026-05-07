<?php

declare(strict_types=1);

use App\Entities\User\Apprentice;
use App\Enums\Auth\AccountStatus;

it('detects suspended status', function () {
    $entity = new Apprentice(
        status: AccountStatus::SUSPENDED,
        isLocked: false,
        setupRequired: false,
    );

    expect($entity->isSuspended())->toBeTrue()
        ->and($entity->isArchived())->toBeFalse()
        ->and($entity->isInactive())->toBeFalse();
});

it('detects archived status', function () {
    $entity = new Apprentice(
        status: AccountStatus::ARCHIVED,
        isLocked: false,
        setupRequired: false,
    );

    expect($entity->isArchived())->toBeTrue()
        ->and($entity->isSuspended())->toBeFalse();
});

it('detects inactive status', function () {
    $entity = new Apprentice(
        status: AccountStatus::INACTIVE,
        isLocked: false,
        setupRequired: false,
    );

    expect($entity->isInactive())->toBeTrue()
        ->and($entity->isSuspended())->toBeFalse();
});

it('detects locked account', function () {
    $entity = new Apprentice(
        status: AccountStatus::VERIFIED,
        isLocked: true,
        setupRequired: false,
    );

    expect($entity->isLocked())->toBeTrue();
});

it('detects unlocked account', function () {
    $entity = new Apprentice(
        status: AccountStatus::VERIFIED,
        isLocked: false,
        setupRequired: false,
    );

    expect($entity->isLocked())->toBeFalse();
});

it('detects setup required', function () {
    $entity = new Apprentice(
        status: AccountStatus::VERIFIED,
        isLocked: false,
        setupRequired: true,
    );

    expect($entity->requiresSetup())->toBeTrue();
});

it('detects no setup required', function () {
    $entity = new Apprentice(
        status: AccountStatus::VERIFIED,
        isLocked: false,
        setupRequired: false,
    );

    expect($entity->requiresSetup())->toBeFalse();
});

it('returns current status', function () {
    $entity = new Apprentice(
        status: AccountStatus::SUSPENDED,
        isLocked: false,
        setupRequired: false,
    );

    expect($entity->status())->toBe(AccountStatus::SUSPENDED);
});
