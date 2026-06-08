<?php

declare(strict_types=1);

use App\Program\Internship\Entities\InternshipState;

test('can be deleted when no placements and no registrations', function () {
    $entity = new InternshipState(placementCount: 0, registrationCount: 0);

    expect($entity->canBeDeleted())->toBeTrue();
});

test('cannot be deleted when has placements', function () {
    $entity = new InternshipState(placementCount: 5, registrationCount: 0);

    expect($entity->canBeDeleted())->toBeFalse();
});

test('cannot be deleted when has registrations', function () {
    $entity = new InternshipState(placementCount: 0, registrationCount: 2);

    expect($entity->canBeDeleted())->toBeFalse();
});