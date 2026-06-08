<?php

declare(strict_types=1);

use App\Partners\Partnership\Enums\PartnershipStatus;
use App\Partners\Partnership\Entities\PartnershipState;

test('is active returns true for active status', function () {
    $entity = new PartnershipState(status: PartnershipStatus::ACTIVE, endDate: null);

    expect($entity->isActive())->toBeTrue();
});

test('is expired returns true for expired status', function () {
    $entity = new PartnershipState(status: PartnershipStatus::EXPIRED, endDate: null);

    expect($entity->isExpired())->toBeTrue();
});

test('is terminated returns true for terminated status', function () {
    $entity = new PartnershipState(status: PartnershipStatus::TERMINATED, endDate: null);

    expect($entity->isTerminated())->toBeTrue();
});

test('is expiring soon returns true within threshold', function () {
    $entity = new PartnershipState(status: PartnershipStatus::ACTIVE, endDate: now()->addDays(15)->toDateString());

    expect($entity->isExpiringSoon(30))->toBeTrue();
});

test('is expiring soon returns false beyond threshold', function () {
    $entity = new PartnershipState(status: PartnershipStatus::ACTIVE, endDate: now()->addDays(60)->toDateString());

    expect($entity->isExpiringSoon(30))->toBeFalse();
});

test('is expiring soon returns false for non-active', function () {
    $entity = new PartnershipState(status: PartnershipStatus::EXPIRED, endDate: now()->addDays(15)->toDateString());

    expect($entity->isExpiringSoon(30))->toBeFalse();
});

test('can be deleted returns true for expired', function () {
    $entity = new PartnershipState(status: PartnershipStatus::EXPIRED, endDate: null);

    expect($entity->canBeDeleted())->toBeTrue();
});

test('can be deleted returns true for terminated', function () {
    $entity = new PartnershipState(status: PartnershipStatus::TERMINATED, endDate: null);

    expect($entity->canBeDeleted())->toBeTrue();
});

test('can be deleted returns false for active', function () {
    $entity = new PartnershipState(status: PartnershipStatus::ACTIVE, endDate: null);

    expect($entity->canBeDeleted())->toBeFalse();
});