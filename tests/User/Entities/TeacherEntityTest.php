<?php

declare(strict_types=1);

use App\User\Entities\TeacherEntity;
use App\User\Enums\AccountStatus;

test('returns correct status', function () {
    $entity = new TeacherEntity(status: AccountStatus::VERIFIED, isLocked: false, setupRequired: false);

    expect($entity->status())->toBe(AccountStatus::VERIFIED);
});

test('detects suspended status', function () {
    $entity = new TeacherEntity(status: AccountStatus::SUSPENDED, isLocked: false, setupRequired: false);

    expect($entity->isSuspended())->toBeTrue();
});

test('detects archived status', function () {
    $entity = new TeacherEntity(status: AccountStatus::ARCHIVED, isLocked: false, setupRequired: false);

    expect($entity->isArchived())->toBeTrue();
});

test('detects inactive status', function () {
    $entity = new TeacherEntity(status: AccountStatus::INACTIVE, isLocked: false, setupRequired: false);

    expect($entity->isInactive())->toBeTrue();
});

test('detects locked account', function () {
    $entity = new TeacherEntity(status: AccountStatus::VERIFIED, isLocked: true, setupRequired: false);

    expect($entity->isLocked())->toBeTrue();
});

test('detects unlocked account', function () {
    $entity = new TeacherEntity(status: AccountStatus::VERIFIED, isLocked: false, setupRequired: false);

    expect($entity->isLocked())->toBeFalse();
});

test('detects setup required', function () {
    $entity = new TeacherEntity(status: AccountStatus::PROVISIONED, isLocked: false, setupRequired: true);

    expect($entity->requiresSetup())->toBeTrue();
});

test('detects no setup required', function () {
    $entity = new TeacherEntity(status: AccountStatus::VERIFIED, isLocked: false, setupRequired: false);

    expect($entity->requiresSetup())->toBeFalse();
});

test('can transition to valid target', function () {
    $entity = new TeacherEntity(status: AccountStatus::VERIFIED, isLocked: false, setupRequired: false);

    expect($entity->canTransitionTo(AccountStatus::SUSPENDED))->toBeTrue();
    expect($entity->canTransitionTo(AccountStatus::INACTIVE))->toBeTrue();
});

test('cannot transition to invalid target', function () {
    $entity = new TeacherEntity(status: AccountStatus::VERIFIED, isLocked: false, setupRequired: false);

    expect($entity->canTransitionTo(AccountStatus::PROVISIONED))->toBeFalse();
});
