<?php

declare(strict_types=1);

use App\Entities\Internship\InternshipPeriod;
use App\Enums\Internship\InternshipStatus;

it('accepts registrations when status allows', function () {
    $entity = new InternshipPeriod(InternshipStatus::ACTIVE);

    expect($entity->isAcceptingRegistrations())->toBeTrue();
});

it('does not accept registrations when status disallows', function () {
    $entity = new InternshipPeriod(InternshipStatus::COMPLETED);

    expect($entity->isAcceptingRegistrations())->toBeFalse();
});

it('does not accept registrations when status is null', function () {
    $entity = new InternshipPeriod(null);

    expect($entity->isAcceptingRegistrations())->toBeFalse();
});
