<?php

declare(strict_types=1);

use App\Program\Internship\Entities\InternshipPeriod;
use App\Program\Internship\Enums\InternshipStatus;
use Carbon\Carbon;

test('internship period is accepting registrations when published and within window', function () {
    $now = Carbon::parse('2025-06-15');
    $period = new InternshipPeriod(
        status: InternshipStatus::PUBLISHED,
        registrationStartDate: Carbon::parse('2025-06-01'),
        registrationEndDate: Carbon::parse('2025-07-01'),
    );
    expect($period->isAcceptingRegistrations($now))->toBeTrue();
});

test('internship period is accepting registrations when active and within window', function () {
    $now = Carbon::parse('2025-06-15');
    $period = new InternshipPeriod(
        status: InternshipStatus::ACTIVE,
        registrationStartDate: Carbon::parse('2025-06-01'),
        registrationEndDate: Carbon::parse('2025-07-01'),
    );
    expect($period->isAcceptingRegistrations($now))->toBeTrue();
});

test('internship period is not accepting registrations when draft', function () {
    $now = Carbon::parse('2025-06-15');
    $period = new InternshipPeriod(
        status: InternshipStatus::DRAFT,
        registrationStartDate: Carbon::parse('2025-06-01'),
        registrationEndDate: Carbon::parse('2025-07-01'),
    );
    expect($period->isAcceptingRegistrations($now))->toBeFalse();
});

test('internship period is not accepting registrations when completed', function () {
    $now = Carbon::parse('2025-06-15');
    $period = new InternshipPeriod(
        status: InternshipStatus::COMPLETED,
        registrationStartDate: Carbon::parse('2025-06-01'),
        registrationEndDate: Carbon::parse('2025-07-01'),
    );
    expect($period->isAcceptingRegistrations($now))->toBeFalse();
});

test('internship period is not accepting registrations before window opens', function () {
    $now = Carbon::parse('2025-05-15');
    $period = new InternshipPeriod(
        status: InternshipStatus::PUBLISHED,
        registrationStartDate: Carbon::parse('2025-06-01'),
        registrationEndDate: Carbon::parse('2025-07-01'),
    );
    expect($period->isAcceptingRegistrations($now))->toBeFalse();
});

test('internship period is not accepting registrations after window closes', function () {
    $now = Carbon::parse('2025-08-01');
    $period = new InternshipPeriod(
        status: InternshipStatus::PUBLISHED,
        registrationStartDate: Carbon::parse('2025-06-01'),
        registrationEndDate: Carbon::parse('2025-07-01'),
    );
    expect($period->isAcceptingRegistrations($now))->toBeFalse();
});

test('internship period is not accepting registrations when no registration dates set', function () {
    $now = Carbon::parse('2025-06-15');
    $period = new InternshipPeriod(status: InternshipStatus::PUBLISHED);
    expect($period->isAcceptingRegistrations($now))->toBeTrue();
});

test('internship period registration window is open when within dates', function () {
    $now = Carbon::parse('2025-06-15');
    $period = new InternshipPeriod(
        status: InternshipStatus::PUBLISHED,
        registrationStartDate: Carbon::parse('2025-06-01'),
        registrationEndDate: Carbon::parse('2025-07-01'),
    );
    expect($period->isRegistrationWindowOpen($now))->toBeTrue();
});

test('internship period registration window is closed before start', function () {
    $now = Carbon::parse('2025-05-01');
    $period = new InternshipPeriod(
        status: InternshipStatus::PUBLISHED,
        registrationStartDate: Carbon::parse('2025-06-01'),
        registrationEndDate: Carbon::parse('2025-07-01'),
    );
    expect($period->isRegistrationWindowOpen($now))->toBeFalse();
});

test('internship period is before registration window', function () {
    $now = Carbon::parse('2025-05-01');
    $period = new InternshipPeriod(
        status: InternshipStatus::PUBLISHED,
        registrationStartDate: Carbon::parse('2025-06-01'),
    );
    expect($period->isBeforeRegistrationWindow($now))->toBeTrue();
});

test('internship period is not before registration window when now is after start', function () {
    $now = Carbon::parse('2025-07-01');
    $period = new InternshipPeriod(
        status: InternshipStatus::PUBLISHED,
        registrationStartDate: Carbon::parse('2025-06-01'),
    );
    expect($period->isBeforeRegistrationWindow($now))->toBeFalse();
});

test('internship period is not before registration window when start date is null', function () {
    $now = Carbon::parse('2025-01-01');
    $period = new InternshipPeriod(status: InternshipStatus::PUBLISHED);
    expect($period->isBeforeRegistrationWindow($now))->toBeFalse();
});

test('internship period is after registration window', function () {
    $now = Carbon::parse('2025-08-01');
    $period = new InternshipPeriod(
        status: InternshipStatus::PUBLISHED,
        registrationEndDate: Carbon::parse('2025-07-01'),
    );
    expect($period->isAfterRegistrationWindow($now))->toBeTrue();
});

test('internship period is not after registration window when now is before end', function () {
    $now = Carbon::parse('2025-06-01');
    $period = new InternshipPeriod(
        status: InternshipStatus::PUBLISHED,
        registrationEndDate: Carbon::parse('2025-07-01'),
    );
    expect($period->isAfterRegistrationWindow($now))->toBeFalse();
});

test('internship period detects academic year presence', function () {
    $withYear = new InternshipPeriod(
        status: InternshipStatus::PUBLISHED,
        academicYearStart: Carbon::parse('2025-01-01'),
        academicYearEnd: Carbon::parse('2025-12-31'),
    );
    expect($withYear->hasAcademicYear())->toBeTrue();

    $withoutYear = new InternshipPeriod(status: InternshipStatus::PUBLISHED);
    expect($withoutYear->hasAcademicYear())->toBeFalse();
});

test('internship period is within academic year when date falls inside', function () {
    $period = new InternshipPeriod(
        status: InternshipStatus::PUBLISHED,
        academicYearStart: Carbon::parse('2025-01-01'),
        academicYearEnd: Carbon::parse('2025-12-31'),
    );
    $date = Carbon::parse('2025-06-15');
    expect($period->isWithinAcademicYear($date))->toBeTrue();
});

test('internship period is not within academic year when date falls outside', function () {
    $period = new InternshipPeriod(
        status: InternshipStatus::PUBLISHED,
        academicYearStart: Carbon::parse('2025-01-01'),
        academicYearEnd: Carbon::parse('2025-12-31'),
    );
    $date = Carbon::parse('2026-01-15');
    expect($period->isWithinAcademicYear($date))->toBeFalse();
});

test('internship period is always within academic year when no academic year set', function () {
    $period = new InternshipPeriod(status: InternshipStatus::PUBLISHED);
    expect($period->isWithinAcademicYear(Carbon::parse('2030-01-01')))->toBeTrue();
});

test('internship period detects dates spanning outside academic year', function () {
    $period = new InternshipPeriod(
        status: InternshipStatus::PUBLISHED,
        academicYearStart: Carbon::parse('2025-01-01'),
        academicYearEnd: Carbon::parse('2025-12-31'),
    );
    $outside = $period->datesSpanOutsideAcademicYear(
        start: Carbon::parse('2025-06-01'),
        end: Carbon::parse('2026-02-01'),
    );
    expect($outside)->toBeTrue();
});

test('internship period dates do not span outside academic year when fully inside', function () {
    $period = new InternshipPeriod(
        status: InternshipStatus::PUBLISHED,
        academicYearStart: Carbon::parse('2025-01-01'),
        academicYearEnd: Carbon::parse('2025-12-31'),
    );
    $inside = $period->datesSpanOutsideAcademicYear(
        start: Carbon::parse('2025-06-01'),
        end: Carbon::parse('2025-08-01'),
    );
    expect($inside)->toBeFalse();
});

test('internship period dates do not span outside when no academic year', function () {
    $period = new InternshipPeriod(status: InternshipStatus::PUBLISHED);
    $result = $period->datesSpanOutsideAcademicYear(
        start: Carbon::parse('2025-01-01'),
        end: Carbon::parse('2026-01-01'),
    );
    expect($result)->toBeFalse();
});

test('internship period dates do not span outside when start or end is null', function () {
    $period = new InternshipPeriod(
        status: InternshipStatus::PUBLISHED,
        academicYearStart: Carbon::parse('2025-01-01'),
        academicYearEnd: Carbon::parse('2025-12-31'),
    );
    expect($period->datesSpanOutsideAcademicYear(start: null, end: Carbon::parse('2026-01-01')))->toBeFalse();
    expect($period->datesSpanOutsideAcademicYear(start: Carbon::parse('2025-01-01'), end: null))->toBeFalse();
});
