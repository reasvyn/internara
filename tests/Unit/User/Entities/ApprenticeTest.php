<?php

declare(strict_types=1);

use App\User\Enums\AccountStatus;
use App\User\Entities\Apprentice;

test('apprentice returns correct status', function () {
    $entity = new Apprentice(status: AccountStatus::ACTIVATED, isLocked: false, setupRequired: false);

    expect($entity->status())->toBe(AccountStatus::ACTIVATED);
});

test('apprentice allows login for activated status', function () {
    $entity = new Apprentice(status: AccountStatus::ACTIVATED, isLocked: false, setupRequired: false);

    expect($entity->status()->allowsLogin())->toBeTrue();
});

test('apprentice blocks login for suspended status', function () {
    $entity = new Apprentice(status: AccountStatus::SUSPENDED, isLocked: false, setupRequired: false);

    expect($entity->isSuspended())->toBeTrue();
    expect($entity->status()->allowsLogin())->toBeFalse();
});

test('apprentice detects archived status', function () {
    $entity = new Apprentice(status: AccountStatus::ARCHIVED, isLocked: false, setupRequired: false);

    expect($entity->isArchived())->toBeTrue();
});

test('apprentice detects inactive status', function () {
    $entity = new Apprentice(status: AccountStatus::INACTIVE, isLocked: false, setupRequired: false);

    expect($entity->isInactive())->toBeTrue();
});

test('apprentice detects locked account', function () {
    $entity = new Apprentice(status: AccountStatus::ACTIVATED, isLocked: true, setupRequired: false);

    expect($entity->isLocked())->toBeTrue();
});

test('apprentice detects unlocked account', function () {
    $entity = new Apprentice(status: AccountStatus::ACTIVATED, isLocked: false, setupRequired: false);

    expect($entity->isLocked())->toBeFalse();
});

test('apprentice detects setup required', function () {
    $entity = new Apprentice(status: AccountStatus::PROVISIONED, isLocked: false, setupRequired: true);

    expect($entity->requiresSetup())->toBeTrue();
});

test('apprentice detects no setup required', function () {
    $entity = new Apprentice(status: AccountStatus::ACTIVATED, isLocked: false, setupRequired: false);

    expect($entity->requiresSetup())->toBeFalse();
});

test('apprentice can transition to valid target', function () {
    $entity = new Apprentice(status: AccountStatus::PROVISIONED, isLocked: false, setupRequired: false);

    expect($entity->canTransitionTo(AccountStatus::ACTIVATED))->toBeTrue();
    expect($entity->canTransitionTo(AccountStatus::SUSPENDED))->toBeTrue();
});

test('apprentice cannot transition to invalid target', function () {
    $entity = new Apprentice(status: AccountStatus::PROVISIONED, isLocked: false, setupRequired: false);

    expect($entity->canTransitionTo(AccountStatus::ARCHIVED))->toBeFalse();
    expect($entity->canTransitionTo(AccountStatus::VERIFIED))->toBeFalse();
});