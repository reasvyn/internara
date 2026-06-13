<?php

declare(strict_types=1);

use App\SysAdmin\Actions\GetAdminDashboardStatsAction;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(LazilyRefreshDatabase::class);

test('returns array with all expected keys', function () {
    $stats = app(GetAdminDashboardStatsAction::class)->execute();

    expect($stats)->toBeArray()->toHaveKeys([
        'totalStudents',
        'totalTeachers',
        'totalSupervisors',
        'totalMentors',
        'totalCompanies',
        'totalPartnerships',
        'totalDepartments',
        'activeInternships',
        'allInternships',
        'registrationsPending',
        'registrationsActive',
        'registrationsCompleted',
        'registrationsTotal',
        'placementTotal',
        'placementFilled',
        'placementCapacity',
        'placementsByInternship',
        'attendanceVerified',
        'attendanceUnverified',
        'logbookVerified',
        'logbookPending',
        'certificatesIssued',
        'certificatesRevoked',
        'certificatesTotal',
        'companiesActive',
        'placementRate',
    ]);
});

test('returns zero counts when no data exists', function () {
    $stats = app(GetAdminDashboardStatsAction::class)->execute();

    expect($stats['totalStudents'])->toBe(0);
    expect($stats['placementRate'])->toBe(0);
});

test('results are cached', function () {
    $key = config('cache-keys.admin_dashboard_stats');

    Cache::shouldReceive('remember')
        ->once()
        ->with($key, 300, Mockery::type('Closure'))
        ->andReturn(['cached' => true]);

    $stats = app(GetAdminDashboardStatsAction::class)->execute();

    expect($stats)->toBe(['cached' => true]);
});
