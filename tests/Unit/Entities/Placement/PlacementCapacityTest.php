<?php

declare(strict_types=1);

use App\Entities\Placement\PlacementCapacity;

it('detects full placement', function () {
    $entity = new PlacementCapacity(10, 10);

    expect($entity->isFull())->toBeTrue();
});

it('detects full when over quota', function () {
    $entity = new PlacementCapacity(10, 15);

    expect($entity->isFull())->toBeTrue();
});

it('detects not full placement', function () {
    $entity = new PlacementCapacity(10, 5);

    expect($entity->isFull())->toBeFalse();
});

it('calculates available slots', function () {
    $entity = new PlacementCapacity(10, 4);

    expect($entity->availableSlots())->toBe(6);
});

it('returns zero available slots when full', function () {
    $entity = new PlacementCapacity(10, 10);

    expect($entity->availableSlots())->toBe(0);
});

it('returns zero available slots when over quota', function () {
    $entity = new PlacementCapacity(10, 15);

    expect($entity->availableSlots())->toBe(0);
});

it('detects available slots exist', function () {
    $entity = new PlacementCapacity(10, 5);

    expect($entity->hasAvailableSlots())->toBeTrue();
});

it('detects no available slots', function () {
    $entity = new PlacementCapacity(10, 10);

    expect($entity->hasAvailableSlots())->toBeFalse();
});
