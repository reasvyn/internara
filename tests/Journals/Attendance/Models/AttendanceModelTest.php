<?php

declare(strict_types=1);

use App\Enrollment\Registration\Models\Registration;
use App\Journals\Attendance\Enums\AttendanceStatus;
use App\Journals\Attendance\Models\Attendance;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;

uses(LazilyRefreshDatabase::class);

test('attendance factory creates valid model', function () {
    $log = Attendance::factory()->create();

    expect($log)->toBeInstanceOf(Attendance::class);
    expect($log->user_id)->not->toBeNull();
    expect($log->registration_id)->not->toBeNull();
    expect($log->date)->not->toBeNull();
});

test('attendance belongs to user', function () {
    $user = User::factory()->create();
    $log = Attendance::factory()->create(['user_id' => $user->id]);

    expect($log->user)->toBeInstanceOf(User::class);
    expect($log->user->id)->toBe($user->id);
});

test('attendance belongs to registration', function () {
    $registration = Registration::factory()->create();
    $log = Attendance::factory()->create(['registration_id' => $registration->id]);

    expect($log->registration)->toBeInstanceOf(Registration::class);
    expect($log->registration->id)->toBe($registration->id);
});

test('attendance casts status to enum', function () {
    $log = Attendance::factory()->create(['status' => AttendanceStatus::PRESENT]);

    expect($log->status)->toBeInstanceOf(AttendanceStatus::class);
    expect($log->status)->toBe(AttendanceStatus::PRESENT);
});

test('attendance casts date to date instance', function () {
    $log = Attendance::factory()->create();

    expect($log->date)->toBeInstanceOf(Carbon::class);
});

test('attendance casts is_verified to boolean', function () {
    $log = Attendance::factory()->create(['is_verified' => true]);

    expect($log->is_verified)->toBeTrue();
});

test('attendance casts clock in and out to time format', function () {
    $log = Attendance::factory()->create([
        'clock_in' => '08:00:00',
        'clock_out' => '17:00:00',
    ]);

    expect($log->clock_in)->not->toBeNull();
    expect($log->clock_out)->not->toBeNull();
});

test('attendance verifier belongs to user', function () {
    $verifier = User::factory()->create();
    $log = Attendance::factory()->create([
        'verified_by' => $verifier->id,
        'is_verified' => true,
    ]);

    expect($log->verifier)->toBeInstanceOf(User::class);
    expect($log->verifier->id)->toBe($verifier->id);
});

test('attendance returns AttendanceStatus entity', function () {
    $log = Attendance::factory()->create(['clock_out' => now()]);

    $status = $log->asAttendanceState();

    expect($status)->toBeInstanceOf(App\Journals\Attendance\Entities\AttendanceState::class);
});

test('attendance fillable attributes are mass assignable', function () {
    $log = Attendance::factory()->create([
        'clock_in_latitude' => -6.2088,
        'clock_in_longitude' => 106.8456,
        'clock_in_ip' => '192.168.1.1',
        'notes' => 'Test notes.',
    ]);

    expect($log->clock_in_latitude)->toBe(-6.2088);
    expect($log->clock_in_longitude)->toBe(106.8456);
    expect($log->clock_in_ip)->toBe('192.168.1.1');
    expect($log->notes)->toBe('Test notes.');
});

test('attendance uses AttendanceFactory', function () {
    $log = Attendance::factory()->create();

    expect($log)->toBeInstanceOf(Attendance::class);
});
