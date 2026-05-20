<?php

declare(strict_types=1);

use App\Domain\Mentee\Entities\MenteeState;
use Carbon\Carbon;

describe('MenteeState', function () {
    it('reports active registration', function () {
        $state = new MenteeState(
            hasActiveRegistration: true,
            startDate: Carbon::now()->subDay(),
            endDate: Carbon::now()->addMonth(),
            isActive: true,
        );

        expect($state->hasActiveRegistration())->toBeTrue()
            ->and($state->isActive())->toBeTrue();
    });

    it('detects within internship period', function () {
        $state = new MenteeState(
            hasActiveRegistration: true,
            startDate: Carbon::now()->subDay(),
            endDate: Carbon::now()->addMonth(),
            isActive: true,
        );

        expect($state->isWithinInternshipPeriod())->toBeTrue();
    });

    it('detects ended internship', function () {
        $state = new MenteeState(
            hasActiveRegistration: false,
            startDate: Carbon::now()->subMonths(3),
            endDate: Carbon::now()->subMonth(),
            isActive: false,
        );

        expect($state->hasEnded())->toBeTrue()
            ->and($state->hasActiveRegistration())->toBeFalse();
    });

    it('returns correct days remaining', function () {
        $state = new MenteeState(
            hasActiveRegistration: true,
            startDate: Carbon::now()->subMonth(),
            endDate: Carbon::now()->addDays(10),
            isActive: true,
        );

        expect($state->daysRemaining())->toBeGreaterThanOrEqual(9);
    });

    it('can clock in when active and within period', function () {
        $state = new MenteeState(
            hasActiveRegistration: true,
            startDate: Carbon::now()->subDay(),
            endDate: Carbon::now()->addMonth(),
            isActive: true,
        );

        expect($state->canClockIn(briefingCompleted: true))->toBeTrue();
    });

    it('cannot clock in without briefing', function () {
        $state = new MenteeState(
            hasActiveRegistration: true,
            startDate: Carbon::now()->subDay(),
            endDate: Carbon::now()->addMonth(),
            isActive: true,
        );

        expect($state->canClockIn(briefingCompleted: false))->toBeFalse();
    });
});
