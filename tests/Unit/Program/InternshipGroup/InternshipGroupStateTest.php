<?php

declare(strict_types=1);

use App\Program\InternshipGroup\Entities\InternshipGroupState;

test('can be deleted when no members', function () {
    $entity = new InternshipGroupState(memberCount: 0, isActive: true);

    expect($entity->canBeDeleted())->toBeTrue();
});

test('cannot be deleted when has members', function () {
    $entity = new InternshipGroupState(memberCount: 3, isActive: true);

    expect($entity->canBeDeleted())->toBeFalse();
});

test('is active returns correct state', function () {
    $entity = new InternshipGroupState(memberCount: 0, isActive: true);

    expect($entity->isActive())->toBeTrue();
});

test('has members returns true when count > 0', function () {
    $entity = new InternshipGroupState(memberCount: 1, isActive: true);

    expect($entity->hasMembers())->toBeTrue();
});