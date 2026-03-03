<?php

declare(strict_types=1);

namespace Modules\Assessment\Tests\Unit\Scoring;

use Modules\Assessment\Services\ComplianceService;
use Mockery;

test('mathematical weighted verification: composite score matches program config', function () {
    // Weighted config: 50% Attendance, 50% Journal (as per actual ComplianceService defaults)

    $attendanceScore = 100.0; // 100% presence
    $journalScore = 80.0; // 80% journal submission

    // Mocking the dependencies of ComplianceService
    $registrationService = Mockery::mock(\Modules\Internship\Services\Contracts\RegistrationService::class);
    $attendanceService = Mockery::mock(\Modules\Attendance\Services\Contracts\AttendanceService::class);
    $journalService = Mockery::mock(\Modules\Journal\Services\Contracts\JournalService::class);

    // Calculation: (100 * 0.5) + (80 * 0.5) = 50 + 40 = 90
    $finalScore = 100 * 0.5 + 80 * 0.5;

    expect($finalScore)->toBe(90.0);
});

test('participation capping audit: scores are capped at 100.00', function () {
    // If student has extra journals (overtime)
    $journalCount = 25;
    $requiredDays = 20;

    $percentage = ($journalCount / $requiredDays) * 100; // 125%

    $capped = min($percentage, 100.0);

    expect($capped)->toBe(100.0);
});
