<?php

declare(strict_types=1);

use App\User\Dashboard\Actions\GetTeacherDashboardStatsAction;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Spatie\Permission\Models\Role;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Role::create(['name' => 'super_admin']);
});

test('returns default zero stats when teacher has no activity', function () {
    $this->actingAsSuperAdmin();

    $action = app(GetTeacherDashboardStatsAction::class);
    $stats = $action->execute();

    expect($stats)->toMatchArray([
        'supervisedStudents' => 0,
        'pendingJournals' => 0,
        'activeCompanies' => 0,
        'ungradedSubmissions' => 0,
        'supervisionLogsCount' => 0,
        'unresolvedIncidents' => 0,
    ]);
});

test('stats are cached', function () {
    $this->actingAsSuperAdmin();

    $action = app(GetTeacherDashboardStatsAction::class);
    $stats = $action->execute();

    $cached = Cache::get(config('cache-keys.admin_dashboard_stats').'teacher.'.auth()->id());

    expect($cached)->toBe($stats);
});
