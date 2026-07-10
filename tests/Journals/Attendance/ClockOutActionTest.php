<?php

declare(strict_types=1);

use App\Core\Exceptions\RejectedException;
use App\Enrollment\Registration\Models\Registration;
use App\Journals\Attendance\Actions\ClockOutAction;
use App\Journals\Attendance\Models\Attendance;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('clocks out successfully', function () {
    $registration = Registration::factory()->create(['status' => 'active']);
    $user = User::factory()->create();
    $user->registrations()->save($registration);

    $log = Attendance::factory()->create([
        'user_id' => $user->id,
        'registration_id' => $registration->id,
        'date' => now()->toDateString(),
        'clock_in' => now()->subHours(8)->toTimeString(),
        'clock_out' => null,
    ]);

    $updated = app(ClockOutAction::class)->execute($user, []);

    expect($updated->id)->toBe($log->id);
    expect($updated->clock_out)->not->toBeNull();
});

test('clocks out with location data', function () {
    $registration = Registration::factory()->create(['status' => 'active']);
    $user = User::factory()->create();
    $user->registrations()->save($registration);

    Attendance::factory()->create([
        'user_id' => $user->id,
        'registration_id' => $registration->id,
        'date' => now()->toDateString(),
        'clock_in' => now()->subHours(8)->toTimeString(),
        'clock_out' => null,
    ]);

    $updated = app(ClockOutAction::class)->execute($user, [
        'latitude' => -6.2088,
        'longitude' => 106.8456,
    ]);

    expect($updated->clock_out_latitude)->toBe(-6.2088);
    expect($updated->clock_out_longitude)->toBe(106.8456);
});

test('clocks out with IP address', function () {
    $registration = Registration::factory()->create(['status' => 'active']);
    $user = User::factory()->create();
    $user->registrations()->save($registration);

    Attendance::factory()->create([
        'user_id' => $user->id,
        'registration_id' => $registration->id,
        'date' => now()->toDateString(),
        'clock_in' => now()->subHours(8)->toTimeString(),
        'clock_out' => null,
    ]);

    $updated = app(ClockOutAction::class)->execute($user, [], requestIp: '192.168.1.1');

    expect($updated->clock_out_ip)->toBe('192.168.1.1');
});

test('throws exception when not clocked in', function () {
    $registration = Registration::factory()->create(['status' => 'active']);
    $user = User::factory()->create();
    $user->registrations()->save($registration);

    app(ClockOutAction::class)->execute($user, []);
})->throws(RejectedException::class, 'You must clock in first.');

test('throws exception when already clocked out', function () {
    $registration = Registration::factory()->create(['status' => 'active']);
    $user = User::factory()->create();
    $user->registrations()->save($registration);

    Attendance::factory()->create([
        'user_id' => $user->id,
        'registration_id' => $registration->id,
        'date' => now()->toDateString(),
        'clock_in' => now()->subHours(8)->toTimeString(),
        'clock_out' => now()->toTimeString(),
    ]);

    app(ClockOutAction::class)->execute($user, []);
})->throws(RejectedException::class, 'Already clocked out for today.');
