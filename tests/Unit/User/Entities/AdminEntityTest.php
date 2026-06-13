<?php

declare(strict_types=1);

use App\User\Entities\AdminEntity;
use App\User\Enums\AccountStatus;

test('returns correct status', function () {
    $entity = new AdminEntity(status: AccountStatus::VERIFIED, isLocked: false, setupRequired: false);

    expect($entity->status())->toBe(AccountStatus::VERIFIED);
});

test('detects suspended status', function () {
    $entity = new AdminEntity(status: AccountStatus::SUSPENDED, isLocked: false, setupRequired: false);

    expect($entity->isSuspended())->toBeTrue();
});

test('detects non-suspended status', function () {
    $entity = new AdminEntity(status: AccountStatus::VERIFIED, isLocked: false, setupRequired: false);

    expect($entity->isSuspended())->toBeFalse();
});

test('detects archived status', function () {
    $entity = new AdminEntity(status: AccountStatus::ARCHIVED, isLocked: false, setupRequired: false);

    expect($entity->isArchived())->toBeTrue();
});

test('detects inactive status', function () {
    $entity = new AdminEntity(status: AccountStatus::INACTIVE, isLocked: false, setupRequired: false);

    expect($entity->isInactive())->toBeTrue();
});

test('detects locked account', function () {
    $entity = new AdminEntity(status: AccountStatus::VERIFIED, isLocked: true, setupRequired: false);

    expect($entity->isLocked())->toBeTrue();
});

test('detects unlocked account', function () {
    $entity = new AdminEntity(status: AccountStatus::VERIFIED, isLocked: false, setupRequired: false);

    expect($entity->isLocked())->toBeFalse();
});

test('detects setup required', function () {
    $entity = new AdminEntity(status: AccountStatus::PROVISIONED, isLocked: false, setupRequired: true);

    expect($entity->requiresSetup())->toBeTrue();
});

test('detects no setup required', function () {
    $entity = new AdminEntity(status: AccountStatus::VERIFIED, isLocked: false, setupRequired: false);

    expect($entity->requiresSetup())->toBeFalse();
});

test('can transition to valid target from provisioned', function () {
    $entity = new AdminEntity(status: AccountStatus::PROVISIONED, isLocked: false, setupRequired: false);

    expect($entity->canTransitionTo(AccountStatus::ACTIVATED))->toBeTrue();
    expect($entity->canTransitionTo(AccountStatus::SUSPENDED))->toBeTrue();
});

test('cannot transition to invalid target from provisioned', function () {
    $entity = new AdminEntity(status: AccountStatus::PROVISIONED, isLocked: false, setupRequired: false);

    expect($entity->canTransitionTo(AccountStatus::ARCHIVED))->toBeFalse();
    expect($entity->canTransitionTo(AccountStatus::VERIFIED))->toBeFalse();
});

test('can transition from verified', function () {
    $entity = new AdminEntity(status: AccountStatus::VERIFIED, isLocked: false, setupRequired: false);

    expect($entity->canTransitionTo(AccountStatus::SUSPENDED))->toBeTrue();
    expect($entity->canTransitionTo(AccountStatus::ARCHIVED))->toBeTrue();
    expect($entity->canTransitionTo(AccountStatus::INACTIVE))->toBeTrue();
    expect($entity->canTransitionTo(AccountStatus::RESTRICTED))->toBeTrue();
});

test('archived status is terminal and cannot transition', function () {
    $entity = new AdminEntity(status: AccountStatus::ARCHIVED, isLocked: false, setupRequired: false);

    expect($entity->canTransitionTo(AccountStatus::ACTIVATED))->toBeFalse();
    expect($entity->canTransitionTo(AccountStatus::VERIFIED))->toBeFalse();
});
