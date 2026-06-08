<?php

declare(strict_types=1);

use App\Enrollment\Registration\Entities\RegistrationState;
use Carbon\Carbon;

test('is active returns true for active status', function () {
    $e = new RegistrationState(status: 'active', startDate: null, endDate: null, hasPlacement: false);

    expect($e->isActive())->toBeTrue();
});

test('is pending returns true for pending status', function () {
    $e = new RegistrationState(status: 'pending', startDate: null, endDate: null, hasPlacement: false);

    expect($e->isPending())->toBeTrue();
});

test('is currently ongoing checks date range', function () {
    $e = new RegistrationState(
        status: 'active',
        startDate: Carbon::yesterday(),
        endDate: Carbon::tomorrow(),
        hasPlacement: false,
    );

    expect($e->isCurrentlyOngoing())->toBeTrue();
});

test('is currently ongoing returns false without dates', function () {
    $e = new RegistrationState(status: 'active', startDate: null, endDate: null, hasPlacement: false);

    expect($e->isCurrentlyOngoing())->toBeFalse();
});

test('has ended returns true when past end date', function () {
    $e = new RegistrationState(
        status: 'active',
        startDate: null,
        endDate: Carbon::yesterday(),
        hasPlacement: false,
    );

    expect($e->hasEnded())->toBeTrue();
});

test('can be approved when pending with placement', function () {
    $e = new RegistrationState(status: 'pending', startDate: null, endDate: null, hasPlacement: true);

    expect($e->canBeApproved())->toBeTrue();
});

test('cannot be approved when pending without placement', function () {
    $e = new RegistrationState(status: 'pending', startDate: null, endDate: null, hasPlacement: false);

    expect($e->canBeApproved())->toBeFalse();
});

test('cannot be approved when active', function () {
    $e = new RegistrationState(status: 'active', startDate: null, endDate: null, hasPlacement: true);

    expect($e->canBeApproved())->toBeFalse();
});

test('days remaining returns 0 without end date', function () {
    $e = new RegistrationState(status: 'active', startDate: null, endDate: null, hasPlacement: false);

    expect($e->daysRemaining())->toBe(0);
});

test('total duration returns days between start and end', function () {
    $e = new RegistrationState(
        status: 'active',
        startDate: Carbon::parse('2025-01-01'),
        endDate: Carbon::parse('2025-12-31'),
        hasPlacement: false,
    );

    expect($e->totalDuration())->toBe(364);
});