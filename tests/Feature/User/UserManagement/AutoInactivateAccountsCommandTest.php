<?php

declare(strict_types=1);

use App\User\Enums\AccountStatus;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Spatie\Permission\Models\Role;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Role::create(['name' => 'super_admin']);
    Role::create(['name' => 'student']);
});

test('inactivates users with no recent activity', function () {
    $user = User::factory()->create([
        'status' => AccountStatus::VERIFIED,
        'last_activity_at' => now()->subDays(100),
    ]);
    $user->assignRole('student');

    $this->artisan('accounts:auto-inactivate', ['--days' => 90])
        ->assertSuccessful()
        ->expectsOutputToContain('completed');

    expect($user->fresh()->status)->toBe(AccountStatus::INACTIVE);
});

test('skips users with recent activity', function () {
    $user = User::factory()->create([
        'status' => AccountStatus::VERIFIED,
        'last_activity_at' => now()->subDays(30),
    ]);
    $user->assignRole('student');

    $this->artisan('accounts:auto-inactivate', ['--days' => 90])
        ->assertSuccessful()
        ->expectsOutputToContain('none');

    expect($user->fresh()->status)->toBe(AccountStatus::VERIFIED);
});

test('skips super admin accounts', function () {
    $user = User::factory()->create([
        'status' => AccountStatus::VERIFIED,
        'last_activity_at' => now()->subDays(100),
    ]);
    $user->assignRole('super_admin');

    $this->artisan('accounts:auto-inactivate', ['--days' => 90])
        ->assertSuccessful()
        ->expectsOutputToContain('none');

    expect($user->fresh()->status)->toBe(AccountStatus::VERIFIED);
});

test('dry run lists accounts without changes', function () {
    $user = User::factory()->create([
        'status' => AccountStatus::VERIFIED,
        'last_activity_at' => now()->subDays(100),
    ]);
    $user->assignRole('student');

    $this->artisan('accounts:auto-inactivate', ['--days' => 90, '--dry-run' => true])
        ->assertSuccessful()
        ->expectsOutputToContain('DRY-RUN');

    expect($user->fresh()->status)->toBe(AccountStatus::VERIFIED);
});

test('handles users with null last activity', function () {
    $user = User::factory()->create([
        'status' => AccountStatus::VERIFIED,
        'last_activity_at' => null,
    ]);
    $user->assignRole('student');

    $this->artisan('accounts:auto-inactivate', ['--days' => 90])
        ->assertSuccessful();

    expect($user->fresh()->status)->toBe(AccountStatus::INACTIVE);
});

test('uses default 90 days when option not provided', function () {
    $active = User::factory()->create([
        'status' => AccountStatus::VERIFIED,
        'last_activity_at' => now()->subDays(89),
    ]);
    $active->assignRole('student');
    $inactive = User::factory()->create([
        'status' => AccountStatus::VERIFIED,
        'last_activity_at' => now()->subDays(91),
    ]);
    $inactive->assignRole('student');

    $this->artisan('accounts:auto-inactivate')
        ->assertSuccessful();

    expect($active->fresh()->status)->toBe(AccountStatus::VERIFIED);
    expect($inactive->fresh()->status)->toBe(AccountStatus::INACTIVE);
});
