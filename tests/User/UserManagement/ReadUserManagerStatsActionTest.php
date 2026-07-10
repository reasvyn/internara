<?php

declare(strict_types=1);

use App\User\Enums\AccountStatus;
use App\User\Models\User;
use App\User\UserManagement\Actions\ReadUserManagerStatsAction;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {});

test('returns correct stats with no users', function () {
    $action = app(ReadUserManagerStatsAction::class);
    $stats = $action->execute();

    expect($stats)->toMatchArray([
        'total' => 0,
        'admins' => 0,
        'active' => 0,
        'pending' => 0,
    ]);
});

test('returns correct total count', function () {
    User::factory()->count(5)->create();

    $action = app(ReadUserManagerStatsAction::class);
    $stats = $action->execute();

    expect($stats['total'])->toBe(5);
});

test('counts super_admin and admin roles as admins', function () {
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole('super_admin');
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $action = app(ReadUserManagerStatsAction::class);
    $stats = $action->execute();

    expect($stats['admins'])->toBe(2);
});

test('counts verified status as active', function () {
    User::factory()->create(['status' => AccountStatus::VERIFIED]);

    $action = app(ReadUserManagerStatsAction::class);
    $stats = $action->execute();

    expect($stats['active'])->toBe(1);
});

test('counts provisioned status as pending', function () {
    User::factory()->create(['status' => AccountStatus::PROVISIONED]);

    $action = app(ReadUserManagerStatsAction::class);
    $stats = $action->execute();

    expect($stats['pending'])->toBe(1);
});

test('active count does not include non-verified statuses', function () {
    User::factory()->count(2)->create(['status' => AccountStatus::SUSPENDED]);

    $action = app(ReadUserManagerStatsAction::class);
    $stats = $action->execute();

    expect($stats['active'])->toBe(0);
});
