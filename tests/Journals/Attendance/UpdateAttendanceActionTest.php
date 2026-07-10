<?php

declare(strict_types=1);

use App\Journals\Attendance\Actions\UpdateAttendanceAction;
use App\Journals\Attendance\Enums\AttendanceStatus;
use App\Journals\Attendance\Models\Attendance;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('updates attendance log date', function () {
    $log = Attendance::factory()->create();

    $updated = app(UpdateAttendanceAction::class)->execute($log, [
        'date' => now()->addDay()->toDateString(),
    ]);

    expect($updated->date->toDateString())->toBe(now()->addDay()->toDateString());
});

test('updates attendance log clock in and out', function () {
    $log = Attendance::factory()->create(['clock_in' => null, 'clock_out' => null]);

    $updated = app(UpdateAttendanceAction::class)->execute($log, [
        'clock_in' => '08:30:00',
        'clock_out' => '17:30:00',
    ]);

    expect($updated->clock_in)->not->toBeNull();
    expect($updated->clock_out)->not->toBeNull();
});

test('updates attendance log status', function () {
    $log = Attendance::factory()->create(['status' => AttendanceStatus::PRESENT]);

    $updated = app(UpdateAttendanceAction::class)->execute($log, [
        'status' => 'sick',
    ]);

    expect($updated->status)->toBe(AttendanceStatus::SICK);
});

test('updates attendance log notes', function () {
    $log = Attendance::factory()->create(['notes' => null]);

    $updated = app(UpdateAttendanceAction::class)->execute($log, [
        'notes' => 'Updated notes.',
    ]);

    expect($updated->notes)->toBe('Updated notes.');
});

test('updates verification when is_verified is true', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');
    $this->actingAs($admin);

    $log = Attendance::factory()->create(['is_verified' => false]);

    $updated = app(UpdateAttendanceAction::class)->execute($log, [
        'is_verified' => true,
    ]);

    expect($updated->is_verified)->toBeTrue();
    expect($updated->verified_by)->toBe($admin->id);
    expect($updated->verified_at)->not->toBeNull();
});

test('partial update does not change unprovided fields', function () {
    $log = Attendance::factory()->create([
        'status' => AttendanceStatus::PRESENT,
        'notes' => 'Original notes.',
    ]);

    app(UpdateAttendanceAction::class)->execute($log, ['status' => 'late']);

    $fresh = $log->fresh();
    expect($fresh->status)->toBe(AttendanceStatus::LATE);
    expect($fresh->notes)->toBe('Original notes.');
});

test('does not change entry when empty data provided', function () {
    $log = Attendance::factory()->create(['notes' => 'Original notes.']);

    app(UpdateAttendanceAction::class)->execute($log, []);

    expect($log->fresh()->notes)->toBe('Original notes.');
});
