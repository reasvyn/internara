<?php

declare(strict_types=1);

use App\Program\Internship\Enums\InternshipStatus;
use App\Program\Internship\Models\Internship;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

afterEach(function () {
    Carbon::setTestNow();
});

describe('7C5WM', function () {
    test('FR-RW2: isAcceptingRegistrations() gates on status and registration window', function () {
        Carbon::setTestNow('2026-08-17');

        $internship = Internship::factory()->create([
            'status' => InternshipStatus::PUBLISHED->value,
            'registration_start_date' => '2026-08-01',
            'registration_end_date' => '2026-08-31',
        ]);

        expect($internship->asInternshipPeriod()->isAcceptingRegistrations())->toBeTrue();
    });

    test('FR-RW2: isAcceptingRegistrations() returns false outside the window or in a non-accepting status', function () {
        Carbon::setTestNow('2026-09-05');

        $afterWindow = Internship::factory()->create([
            'status' => InternshipStatus::PUBLISHED->value,
            'registration_start_date' => '2026-08-01',
            'registration_end_date' => '2026-08-31',
        ]);

        expect($afterWindow->asInternshipPeriod()->isAcceptingRegistrations())->toBeFalse();

        $draft = Internship::factory()->create([
            'status' => InternshipStatus::DRAFT->value,
            'registration_start_date' => '2026-08-01',
            'registration_end_date' => '2026-08-31',
        ]);

        expect($draft->asInternshipPeriod()->isAcceptingRegistrations())->toBeFalse();
    });

    test('FR-RW3: isRegistrationWindowOpen() checks only the date range, ignoring status', function () {
        Carbon::setTestNow('2026-08-17');

        $internship = Internship::factory()->create([
            'status' => InternshipStatus::DRAFT->value,
            'registration_start_date' => '2026-08-01',
            'registration_end_date' => '2026-08-31',
        ]);

        expect($internship->asInternshipPeriod()->isRegistrationWindowOpen())->toBeTrue();
    });
});
