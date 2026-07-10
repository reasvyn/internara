<?php

declare(strict_types=1);

use App\Program\Internship\Entities\InternshipState;

test('internship state can be deleted when no placements or registrations', function () {
    $state = new InternshipState(0, 0);
    expect($state->canBeDeleted())->toBeTrue();
});

test('internship state cannot be deleted when placements exist', function () {
    $state = new InternshipState(1, 0);
    expect($state->canBeDeleted())->toBeFalse();
});

test('internship state cannot be deleted when registrations exist', function () {
    $state = new InternshipState(0, 1);
    expect($state->canBeDeleted())->toBeFalse();
});

test('internship state cannot be deleted when both placements and registrations exist', function () {
    $state = new InternshipState(3, 5);
    expect($state->canBeDeleted())->toBeFalse();
});
