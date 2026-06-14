<?php

declare(strict_types=1);

use App\Enrollment\Registration\Models\Registration;
use App\Journals\Attendance\Actions\CreateAttendanceAction;
use App\Journals\Attendance\Enums\AttendanceStatus;
use App\Journals\Attendance\Models\Attendance;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('creates attendance log with valid data', function () {
    $user = User::factory()->create();
    $registration = Registration::factory()->create(['status' => 'active']);

    $log = app(CreateAttendanceAction::class)->execute($user, [
        'registration_id' => $registration->id,
        'date' => now()->toDateString(),
    ]);

    expect($log)->toBeInstanceOf(Attendance::class);
    $this->assertModelExists($log);
    expect($log->user_id)->toBe($user->id);
    expect($log->status)->toBe(AttendanceStatus::PRESENT);
    expect($log->is_verified)->toBeFalse();
});

test('creates attendance log with clock in and out times', function () {
    $user = User::factory()->create();
    $registration = Registration::factory()->create(['status' => 'active']);

    $log = app(CreateAttendanceAction::class)->execute($user, [
        'registration_id' => $registration->id,
        'date' => now()->toDateString(),
        'clock_in' => '08:00:00',
        'clock_out' => '17:00:00',
    ]);

    expect($log->clock_in)->not->toBeNull();
    expect($log->clock_out)->not->toBeNull();
});

test('creates attendance log with custom status and notes', function () {
    $user = User::factory()->create();
    $registration = Registration::factory()->create(['status' => 'active']);

    $log = app(CreateAttendanceAction::class)->execute($user, [
        'registration_id' => $registration->id,
        'date' => now()->toDateString(),
        'status' => 'late',
        'notes' => 'Arrived late due to traffic.',
    ]);

    expect($log->status)->toBe(AttendanceStatus::LATE);
    expect($log->notes)->toBe('Arrived late due to traffic.');
});

test('creates verified attendance log when authenticated', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');
    $this->actingAs($admin);

    $user = User::factory()->create();
    $registration = Registration::factory()->create(['status' => 'active']);

    $log = app(CreateAttendanceAction::class)->execute($user, [
        'registration_id' => $registration->id,
        'date' => now()->toDateString(),
        'is_verified' => true,
    ]);

    expect($log->is_verified)->toBeTrue();
    expect($log->verified_by)->toBe($admin->id);
    expect($log->verified_at)->not->toBeNull();
});

test('creates attendance log with absent status', function () {
    $user = User::factory()->create();
    $registration = Registration::factory()->create(['status' => 'active']);

    $log = app(CreateAttendanceAction::class)->execute($user, [
        'registration_id' => $registration->id,
        'date' => now()->toDateString(),
        'status' => 'absent',
    ]);

    expect($log->status)->toBe(AttendanceStatus::ABSENT);
});
