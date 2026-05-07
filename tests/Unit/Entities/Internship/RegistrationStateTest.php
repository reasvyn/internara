<?php

declare(strict_types=1);

use App\Entities\Internship\RegistrationState;
use Carbon\Carbon;

it('detects active registration', function () {
    $entity = new RegistrationState('active', null, null, false);

    expect($entity->isActive())->toBeTrue()
        ->and($entity->isPending())->toBeFalse();
});

it('detects pending registration', function () {
    $entity = new RegistrationState('pending', null, null, false);

    expect($entity->isPending())->toBeTrue()
        ->and($entity->isActive())->toBeFalse();
});

it('detects ongoing period', function () {
    $entity = new RegistrationState('active', Carbon::yesterday(), Carbon::tomorrow(), false);

    expect($entity->isCurrentlyOngoing())->toBeTrue();
});

it('detects ended period', function () {
    $entity = new RegistrationState('active', Carbon::parse('-10 days'), Carbon::yesterday(), false);

    expect($entity->hasEnded())->toBeTrue()
        ->and($entity->isCurrentlyOngoing())->toBeFalse();
});

it('returns false for ongoing when dates missing', function () {
    $entity = new RegistrationState('active', null, null, false);

    expect($entity->isCurrentlyOngoing())->toBeFalse();
});

it('can be approved when pending with placement', function () {
    $entity = new RegistrationState('pending', null, null, true);

    expect($entity->canBeApproved())->toBeTrue();
});

it('cannot be approved when pending without placement', function () {
    $entity = new RegistrationState('pending', null, null, false);

    expect($entity->canBeApproved())->toBeFalse();
});

it('cannot be approved when not pending', function () {
    $entity = new RegistrationState('active', null, null, true);

    expect($entity->canBeApproved())->toBeFalse();
});

it('calculates days remaining', function () {
    $entity = new RegistrationState('active', Carbon::parse('-10 days'), Carbon::parse('+5 days'), false);

    expect($entity->daysRemaining())->toBe(5);
});

it('returns zero days remaining when past end', function () {
    $entity = new RegistrationState('active', Carbon::parse('-10 days'), Carbon::yesterday(), false);

    expect($entity->daysRemaining())->toBe(0);
});

it('returns zero days remaining when no end date', function () {
    $entity = new RegistrationState('active', Carbon::yesterday(), null, false);

    expect($entity->daysRemaining())->toBe(0);
});

it('calculates total duration', function () {
    $entity = new RegistrationState('active', Carbon::parse('2026-01-01'), Carbon::parse('2026-06-30'), false);

    expect($entity->totalDuration())->toBe(180);
});

it('returns zero total duration when dates missing', function () {
    $entity = new RegistrationState('active', null, null, false);

    expect($entity->totalDuration())->toBe(0);
});
