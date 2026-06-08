<?php

declare(strict_types=1);

use App\Academics\Department\Entities\DepartmentState;

test('can be deleted returns true when no profiles', function () {
    $entity = new DepartmentState(profileCount: 0, hasProfiles: false);

    expect($entity->canBeDeleted())->toBeTrue();
});

test('can be deleted returns false when has profiles', function () {
    $entity = new DepartmentState(profileCount: 3, hasProfiles: true);

    expect($entity->canBeDeleted())->toBeFalse();
});

test('has profiles returns true when profiles exist', function () {
    $entity = new DepartmentState(profileCount: 5, hasProfiles: true);

    expect($entity->canBeDeleted())->toBeFalse();
});