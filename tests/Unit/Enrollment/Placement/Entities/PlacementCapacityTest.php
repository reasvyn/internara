<?php

declare(strict_types=1);

use App\Enrollment\Placement\Entities\PlacementCapacity;

test('is full when filled quota equals quota', function () {
    $e = new PlacementCapacity(quota: 10, filledQuota: 10);

    expect($e->isFull())->toBeTrue();
});

test('is not full when filled quota is below quota', function () {
    $e = new PlacementCapacity(quota: 10, filledQuota: 5);

    expect($e->isFull())->toBeFalse();
});

test('available slots returns difference', function () {
    $e = new PlacementCapacity(quota: 10, filledQuota: 7);

    expect($e->availableSlots())->toBe(3);
});

test('available slots returns 0 when full', function () {
    $e = new PlacementCapacity(quota: 10, filledQuota: 15);

    expect($e->availableSlots())->toBe(0);
});

test('has available slots returns true when slots exist', function () {
    $e = new PlacementCapacity(quota: 10, filledQuota: 8);

    expect($e->hasAvailableSlots())->toBeTrue();
});
