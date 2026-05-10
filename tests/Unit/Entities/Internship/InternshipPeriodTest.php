<?php

declare(strict_types=1);

use App\Entities\Internship\InternshipPeriod;
use App\Enums\Internship\InternshipStatus;

describe('without registration window', function () {

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

});

describe('with registration window', function () {

    it('accepts registrations when within window', function () {
        $entity = new InternshipPeriod(
            InternshipStatus::PUBLISHED,
            now()->subWeek(),
            now()->addWeek(),
        );

        expect($entity->isAcceptingRegistrations())->toBeTrue();
        expect($entity->isRegistrationWindowOpen())->toBeTrue();
    });

    it('rejects registrations when before window opens', function () {
        $entity = new InternshipPeriod(
            InternshipStatus::PUBLISHED,
            now()->addWeek(),
            now()->addMonth(),
        );

        expect($entity->isAcceptingRegistrations())->toBeFalse();
        expect($entity->isRegistrationWindowOpen())->toBeFalse();
        expect($entity->isBeforeRegistrationWindow())->toBeTrue();
    });

    it('rejects registrations when after window closes', function () {
        $entity = new InternshipPeriod(
            InternshipStatus::PUBLISHED,
            now()->subMonth(),
            now()->subWeek(),
        );

        expect($entity->isAcceptingRegistrations())->toBeFalse();
        expect($entity->isRegistrationWindowOpen())->toBeFalse();
        expect($entity->isAfterRegistrationWindow())->toBeTrue();
    });

    it('rejects registrations when window closed and status is active', function () {
        $entity = new InternshipPeriod(
            InternshipStatus::ACTIVE,
            now()->subMonth(),
            now()->subWeek(),
        );

        expect($entity->isAcceptingRegistrations())->toBeFalse();
    });

    it('accepts registrations on the exact start date', function () {
        $entity = new InternshipPeriod(
            InternshipStatus::PUBLISHED,
            now()->startOfDay(),
            now()->addWeek(),
        );

        expect($entity->isAcceptingRegistrations())->toBeTrue();
    });

    it('accepts registrations on the exact end date', function () {
        $entity = new InternshipPeriod(
            InternshipStatus::PUBLISHED,
            now()->subWeek(),
            now()->endOfDay(),
        );

        expect($entity->isAcceptingRegistrations())->toBeTrue();
    });

});
