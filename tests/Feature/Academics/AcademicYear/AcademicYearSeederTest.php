<?php

declare(strict_types=1);

namespace Tests\Feature\Academics\AcademicYear;

use App\Academics\AcademicYear\Models\AcademicYear;
use Carbon\Carbon;
use Database\Seeders\AcademicYearSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('AcademicYearSeeder seeds YY-1/YY when month is June or earlier', function () {
    // Set time to June 2026 (Month = 6)
    Carbon::setTestNow(Carbon::create(2026, 6, 15));

    $seeder = new AcademicYearSeeder;
    $seeder->run();

    $activeYear = AcademicYear::where('is_active', true)->first();

    expect($activeYear)->not->toBeNull();
    expect($activeYear->name)->toBe('2025/2026');
    expect($activeYear->start_date->format('Y-m-d'))->toBe('2025-07-01');
    expect($activeYear->end_date->format('Y-m-d'))->toBe('2026-06-30');
});

test('AcademicYearSeeder seeds YY/YY+1 when month is July or later', function () {
    // Set time to July 2026 (Month = 7)
    Carbon::setTestNow(Carbon::create(2026, 7, 15));

    $seeder = new AcademicYearSeeder;
    $seeder->run();

    $activeYear = AcademicYear::where('is_active', true)->first();

    expect($activeYear)->not->toBeNull();
    expect($activeYear->name)->toBe('2026/2027');
    expect($activeYear->start_date->format('Y-m-d'))->toBe('2026-07-01');
    expect($activeYear->end_date->format('Y-m-d'))->toBe('2027-06-30');
});

afterEach(function () {
    Carbon::setTestNow(); // Reset test time
});
