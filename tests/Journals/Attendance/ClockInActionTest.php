<?php

declare(strict_types=1);

use App\Core\Exceptions\RejectedException;
use App\Enrollment\Registration\Models\Registration;
use App\Journals\Attendance\Actions\ClockInAction;
use App\Journals\Attendance\Enums\AttendanceStatus;
use App\Journals\Attendance\Models\Attendance;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('clocks in successfully', function () {
    $registration = Registration::factory()->create(['status' => 'active']);
    $user = User::factory()->create();
    $user->registrations()->save($registration);

    $log = app(ClockInAction::class)->execute($user, []);

    expect($log)->toBeInstanceOf(Attendance::class);
    expect($log->user_id)->toBe($user->id);
    expect($log->registration_id)->toBe($registration->id);
    expect($log->date->toDateString())->toBe(now()->toDateString());
    expect($log->clock_in)->not->toBeNull();
    expect($log->status)->toBe(AttendanceStatus::PRESENT);
});

test('clocks in with location data', function () {
    $registration = Registration::factory()->create(['status' => 'active']);
    $user = User::factory()->create();
    $user->registrations()->save($registration);

    $log = app(ClockInAction::class)->execute($user, [
        'latitude' => -6.2088,
        'longitude' => 106.8456,
    ]);

    expect($log->clock_in_latitude)->toBe(-6.2088);
    expect($log->clock_in_longitude)->toBe(106.8456);
});

test('clocks in with IP address', function () {
    $registration = Registration::factory()->create(['status' => 'active']);
    $user = User::factory()->create();
    $user->registrations()->save($registration);

    $log = app(ClockInAction::class)->execute($user, [], requestIp: '192.168.1.1');

    expect($log->clock_in_ip)->toBe('192.168.1.1');
});

test('throws exception when user has no active registration', function () {
    $user = User::factory()->create();

    app(ClockInAction::class)->execute($user, []);
})->throws(RejectedException::class, 'No active internship registration found.');

test('throws exception when already clocked in today', function () {
    $registration = Registration::factory()->create(['status' => 'active']);
    $user = User::factory()->create();
    $user->registrations()->save($registration);

    Attendance::factory()->create([
        'user_id' => $user->id,
        'registration_id' => $registration->id,
        'date' => now()->toDateString(),
    ]);

    app(ClockInAction::class)->execute($user, []);
})->throws(RejectedException::class, 'Already clocked in for today.');
