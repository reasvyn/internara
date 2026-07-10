<?php

declare(strict_types=1);

use App\Program\InternshipGroup\Entities\InternshipGroupState;

test('internship group state detects active', function () {
    $active = new InternshipGroupState(0, true);
    expect($active->isActive())->toBeTrue();

    $inactive = new InternshipGroupState(0, false);
    expect($inactive->isActive())->toBeFalse();
});

test('internship group state detects members', function () {
    $withMembers = new InternshipGroupState(3, true);
    expect($withMembers->hasMembers())->toBeTrue();

    $empty = new InternshipGroupState(0, true);
    expect($empty->hasMembers())->toBeFalse();
});

test('internship group state can be deleted when no members', function () {
    $empty = new InternshipGroupState(0, true);
    expect($empty->canBeDeleted())->toBeTrue();

    $withMembers = new InternshipGroupState(2, false);
    expect($withMembers->canBeDeleted())->toBeFalse();
});
