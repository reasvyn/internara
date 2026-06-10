<?php

declare(strict_types=1);

use App\Partners\Company\Entities\CompanyState;

test('can be deleted when no placements and no partnerships', function () {
    $entity = new CompanyState(placementCount: 0, partnershipCount: 0);

    expect($entity->canBeDeleted())->toBeTrue();
});

test('cannot be deleted when has placements', function () {
    $entity = new CompanyState(placementCount: 3, partnershipCount: 0);

    expect($entity->canBeDeleted())->toBeFalse();
});

test('cannot be deleted when has partnerships', function () {
    $entity = new CompanyState(placementCount: 0, partnershipCount: 1);

    expect($entity->canBeDeleted())->toBeFalse();
});
