<?php

declare(strict_types=1);

use App\Program\Internship\Entities\InternshipPeriod;
use App\Program\Internship\Enums\InternshipStatus;
use Carbon\Carbon;

describe('isAcceptingRegistrations', function () {
    it('returns true for active status within registration window', function () {
        $entity = new InternshipPeriod(
            status: InternshipStatus::ACTIVE,
            registrationStartDate: Carbon::yesterday(),
            registrationEndDate: Carbon::tomorrow(),
        );

        expect($entity->isAcceptingRegistrations())->toBeTrue();
    });

    it('returns false when status does not accept registrations', function () {
        $entity = new InternshipPeriod(status: InternshipStatus::DRAFT);

        expect($entity->isAcceptingRegistrations())->toBeFalse();
    });

    it('returns false before registration window opens', function () {
        $entity = new InternshipPeriod(
            status: InternshipStatus::PUBLISHED,
            registrationStartDate: Carbon::tomorrow(),
            registrationEndDate: Carbon::tomorrow()->addDays(30),
        );

        expect($entity->isAcceptingRegistrations())->toBeFalse();
    });

    it('returns false after registration window closes', function () {
        $entity = new InternshipPeriod(
            status: InternshipStatus::ACTIVE,
            registrationStartDate: Carbon::yesterday()->addDays(-30),
            registrationEndDate: Carbon::yesterday(),
        );

        expect($entity->isAcceptingRegistrations())->toBeFalse();
    });

    it('returns false when null status', function () {
        $entity = new InternshipPeriod(status: null);

        expect($entity->isAcceptingRegistrations())->toBeFalse();
    });
});

describe('registration window', function () {
    it('is open within window', function () {
        $entity = new InternshipPeriod(
            status: InternshipStatus::PUBLISHED,
            registrationStartDate: Carbon::yesterday(),
            registrationEndDate: Carbon::tomorrow(),
        );

        expect($entity->isRegistrationWindowOpen())->toBeTrue();
    });

    it('is before window when start date not reached', function () {
        $entity = new InternshipPeriod(
            status: InternshipStatus::PUBLISHED,
            registrationStartDate: Carbon::tomorrow(),
        );

        expect($entity->isBeforeRegistrationWindow())->toBeTrue();
    });

    it('is after window when end date passed', function () {
        $entity = new InternshipPeriod(
            status: InternshipStatus::ACTIVE,
            registrationEndDate: Carbon::yesterday(),
        );

        expect($entity->isAfterRegistrationWindow())->toBeTrue();
    });
});

describe('academic year', function () {
    it('has academic year when both dates set', function () {
        $entity = new InternshipPeriod(
            status: InternshipStatus::ACTIVE,
            academicYearStart: Carbon::parse('2025-01-01'),
            academicYearEnd: Carbon::parse('2025-12-31'),
        );

        expect($entity->hasAcademicYear())->toBeTrue();
    });

    it('is within academic year', function () {
        $entity = new InternshipPeriod(
            status: InternshipStatus::ACTIVE,
            academicYearStart: Carbon::parse('2025-01-01'),
            academicYearEnd: Carbon::parse('2025-12-31'),
        );

        expect($entity->isWithinAcademicYear(Carbon::parse('2025-06-01')))->toBeTrue();
    });
});