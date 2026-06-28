<?php

declare(strict_types=1);

use App\Enrollment\Registration\Actions\ReadRegistrationAvailabilityAction;
use App\Settings\Services\Settings;
use Carbon\Carbon;

describe('ReadRegistrationAvailabilityAction', function () {
    beforeEach(function () {
        Settings::clearOverrides();
    });

    it('returns not_configured when registration period settings are missing', function () {
        $result = app(ReadRegistrationAvailabilityAction::class)->execute();

        expect($result)->toMatchArray(['status' => 'not_configured']);
    });

    it('returns open when now is between start and end dates', function () {
        Settings::override([
            'registration_period_start' => Carbon::yesterday()->toDateString(),
            'registration_period_end' => Carbon::tomorrow()->toDateString(),
        ]);

        $result = app(ReadRegistrationAvailabilityAction::class)->execute();

        expect($result['status'])->toBe('open');
        expect($result)->toHaveKeys(['start_date', 'end_date']);
    });

    it('returns upcoming when start date is within one month from now', function () {
        Settings::override([
            'registration_period_start' => Carbon::tomorrow()->toDateString(),
            'registration_period_end' => Carbon::parse('+2 months')->toDateString(),
        ]);

        $result = app(ReadRegistrationAvailabilityAction::class)->execute();

        expect($result['status'])->toBe('upcoming');
    });

    it('returns closed when registration period has passed', function () {
        Settings::override([
            'registration_period_start' => Carbon::parse('-2 months')->toDateString(),
            'registration_period_end' => Carbon::yesterday()->toDateString(),
        ]);

        $result = app(ReadRegistrationAvailabilityAction::class)->execute();

        expect($result['status'])->toBe('closed');
    });
});
