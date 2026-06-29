<?php

declare(strict_types=1);

use App\User\Dashboard\Actions\ReadTeacherDashboardAction;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');
    $this->actingAs($admin);
});

test('returns default zero stats when teacher has no activity', function () {
    $action = app(ReadTeacherDashboardAction::class);
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
    $action = app(ReadTeacherDashboardAction::class);
    $stats = $action->execute();

    $cached = Cache::get(config('cache-keys.admin_dashboard_stats').'teacher.'.auth()->id());

    expect($cached)->toBe($stats);
});
