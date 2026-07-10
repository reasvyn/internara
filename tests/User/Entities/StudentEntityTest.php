<?php

declare(strict_types=1);

use App\User\Entities\StudentEntity;
use App\User\Enums\AccountStatus;

test('returns correct status', function () {
    $entity = new StudentEntity(status: AccountStatus::ACTIVATED, isLocked: false, setupRequired: false);

    expect($entity->status())->toBe(AccountStatus::ACTIVATED);
});

test('detects suspended status', function () {
    $entity = new StudentEntity(status: AccountStatus::SUSPENDED, isLocked: false, setupRequired: false);

    expect($entity->isSuspended())->toBeTrue();
});

test('detects archived status', function () {
    $entity = new StudentEntity(status: AccountStatus::ARCHIVED, isLocked: false, setupRequired: false);

    expect($entity->isArchived())->toBeTrue();
});

test('detects inactive status', function () {
    $entity = new StudentEntity(status: AccountStatus::INACTIVE, isLocked: false, setupRequired: false);

    expect($entity->isInactive())->toBeTrue();
});

test('detects locked account', function () {
    $entity = new StudentEntity(status: AccountStatus::ACTIVATED, isLocked: true, setupRequired: false);

    expect($entity->isLocked())->toBeTrue();
});

test('detects unlocked account', function () {
    $entity = new StudentEntity(status: AccountStatus::ACTIVATED, isLocked: false, setupRequired: false);

    expect($entity->isLocked())->toBeFalse();
});

test('detects setup required', function () {
    $entity = new StudentEntity(status: AccountStatus::PROVISIONED, isLocked: false, setupRequired: true);

    expect($entity->requiresSetup())->toBeTrue();
});

test('detects no setup required', function () {
    $entity = new StudentEntity(status: AccountStatus::ACTIVATED, isLocked: false, setupRequired: false);

    expect($entity->requiresSetup())->toBeFalse();
});

test('reports active registration', function () {
    $entity = new StudentEntity(status: AccountStatus::ACTIVATED, isLocked: false, setupRequired: false, hasActiveRegistration: true);

    expect($entity->isRegistered())->toBeTrue();
});

test('reports no active registration', function () {
    $entity = new StudentEntity(status: AccountStatus::ACTIVATED, isLocked: false, setupRequired: false);

    expect($entity->isRegistered())->toBeFalse();
});

test('reports placement', function () {
    $entity = new StudentEntity(status: AccountStatus::ACTIVATED, isLocked: false, setupRequired: false, hasPlacement: true);

    expect($entity->isPlaced())->toBeTrue();
});

test('reports no placement', function () {
    $entity = new StudentEntity(status: AccountStatus::ACTIVATED, isLocked: false, setupRequired: false);

    expect($entity->isPlaced())->toBeFalse();
});

test('can transition to valid target', function () {
    $entity = new StudentEntity(status: AccountStatus::PROVISIONED, isLocked: false, setupRequired: false);

    expect($entity->canTransitionTo(AccountStatus::ACTIVATED))->toBeTrue();
});

test('cannot transition to invalid target', function () {
    $entity = new StudentEntity(status: AccountStatus::ARCHIVED, isLocked: false, setupRequired: false);

    expect($entity->canTransitionTo(AccountStatus::ACTIVATED))->toBeFalse();
});
