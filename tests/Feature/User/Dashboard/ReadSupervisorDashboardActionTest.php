<?php

declare(strict_types=1);

use App\User\Dashboard\Actions\ReadSupervisorDashboardAction;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {});

test('returns default zero stats when supervisor has no activity', function () {
    $this->actingAsSuperAdmin();

    $action = app(ReadSupervisorDashboardAction::class);
    $stats = $action->execute();

    expect($stats)->toMatchArray([
        'activeInterns' => 0,
        'pendingEvaluations' => 0,
        'verifiedJournals' => 0,
        'pendingJournals' => 0,
        'pendingAttendance' => 0,
    ]);
});

test('stats are cached', function () {
    $this->actingAsSuperAdmin();

    $action = app(ReadSupervisorDashboardAction::class);
    $stats = $action->execute();

    $cached = Cache::get(config('cache-keys.admin_dashboard_stats').'supervisor.'.auth()->id());

    expect($cached)->toBe($stats);
});
