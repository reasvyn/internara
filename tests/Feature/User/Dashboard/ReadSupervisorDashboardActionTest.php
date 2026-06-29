<?php

declare(strict_types=1);

use App\User\Dashboard\Actions\ReadSupervisorDashboardAction;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');
    $this->actingAs($admin);
});

test('returns default zero stats when supervisor has no activity', function () {
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
    $action = app(ReadSupervisorDashboardAction::class);
    $stats = $action->execute();

    $cached = Cache::get(config('cache-keys.admin_dashboard_stats').'supervisor.'.auth()->id());

    expect($cached)->toBe($stats);
});
