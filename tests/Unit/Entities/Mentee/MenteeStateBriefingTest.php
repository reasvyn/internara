<?php

declare(strict_types=1);

use App\Entities\Mentee\MenteeState;
use Carbon\Carbon;

describe('briefing gate', function () {
    it('allows clock-in when briefing is completed', function () {
        $state = new MenteeState(
            hasActiveRegistration: true,
            startDate: Carbon::yesterday(),
            endDate: Carbon::tomorrow(),
            isActive: true,
        );

        expect($state->canClockIn(briefingCompleted: true))->toBeTrue();
    });

    it('blocks clock-in when briefing is not completed', function () {
        $state = new MenteeState(
            hasActiveRegistration: true,
            startDate: Carbon::yesterday(),
            endDate: Carbon::tomorrow(),
            isActive: true,
        );

        expect($state->canClockIn(briefingCompleted: false))->toBeFalse();
    });

    it('allows logbook submission when briefing is completed', function () {
        $state = new MenteeState(
            hasActiveRegistration: true,
            startDate: Carbon::yesterday(),
            endDate: Carbon::tomorrow(),
            isActive: true,
        );

        expect($state->canSubmitLogbook(briefingCompleted: true))->toBeTrue();
    });

    it('blocks logbook submission when briefing is not completed', function () {
        $state = new MenteeState(
            hasActiveRegistration: true,
            startDate: Carbon::yesterday(),
            endDate: Carbon::tomorrow(),
            isActive: true,
        );

        expect($state->canSubmitLogbook(briefingCompleted: false))->toBeFalse();
    });

    it('defaults to briefing completed for backward compatibility', function () {
        $state = new MenteeState(
            hasActiveRegistration: true,
            startDate: Carbon::yesterday(),
            endDate: Carbon::tomorrow(),
            isActive: true,
        );

        expect($state->canClockIn())->toBeTrue();
        expect($state->canSubmitLogbook())->toBeTrue();
    });
});
