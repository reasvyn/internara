<?php

declare(strict_types=1);

use App\Academics\Department\Entities\DepartmentState;

test('department state can be deleted when no profiles exist', function () {
    $noProfiles = new DepartmentState(profileCount: 0, hasProfiles: false);
    expect($noProfiles->canBeDeleted())->toBeTrue();

    $hasProfiles = new DepartmentState(profileCount: 5, hasProfiles: true);
    expect($hasProfiles->canBeDeleted())->toBeFalse();
});
