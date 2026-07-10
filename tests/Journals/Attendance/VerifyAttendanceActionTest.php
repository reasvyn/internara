<?php

declare(strict_types=1);

use App\Journals\Attendance\Actions\VerifyAttendanceAction;
use App\Journals\Attendance\Models\Attendance;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('verifies attendance log', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');
    $this->actingAs($admin);

    $log = Attendance::factory()->create(['is_verified' => false]);

    $verified = app(VerifyAttendanceAction::class)->execute($log);

    expect($verified->is_verified)->toBeTrue();
    expect($verified->verified_by)->toBe($admin->id);
    expect($verified->verified_at)->not->toBeNull();
});

test('verifies attendance log with admin role', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $this->actingAs($admin);

    $log = Attendance::factory()->create(['is_verified' => false]);

    $verified = app(VerifyAttendanceAction::class)->execute($log);

    expect($verified->is_verified)->toBeTrue();
});
