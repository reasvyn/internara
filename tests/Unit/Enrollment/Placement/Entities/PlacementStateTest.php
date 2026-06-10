<?php

declare(strict_types=1);

use App\Enrollment\Placement\Entities\PlacementState;

test('can be deleted when no registrations', function () {
    $e = new PlacementState(registrationCount: 0);

    expect($e->canBeDeleted())->toBeTrue();
});

test('cannot be deleted when has registrations', function () {
    $e = new PlacementState(registrationCount: 5);

    expect($e->canBeDeleted())->toBeFalse();
});
