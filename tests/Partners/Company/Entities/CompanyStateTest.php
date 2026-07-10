<?php

declare(strict_types=1);

use App\Partners\Company\Entities\CompanyState;

test('company state can be deleted when no placements and no partnerships', function () {
    $deletable = new CompanyState(placementCount: 0, partnershipCount: 0);
    expect($deletable->canBeDeleted())->toBeTrue();

    $hasPlacements = new CompanyState(placementCount: 3, partnershipCount: 0);
    expect($hasPlacements->canBeDeleted())->toBeFalse();

    $hasPartnerships = new CompanyState(placementCount: 0, partnershipCount: 2);
    expect($hasPartnerships->canBeDeleted())->toBeFalse();

    $hasBoth = new CompanyState(placementCount: 1, partnershipCount: 1);
    expect($hasBoth->canBeDeleted())->toBeFalse();
});
