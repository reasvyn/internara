<?php

declare(strict_types=1);

use App\Models\Attendance;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can be created with factory', function () {
    $log = Attendance::factory()->create();

    expect($log)->toBeInstanceOf(Attendance::class)
        ->and($log->id)->toBeUuid();
});

it('casts attributes correctly', function () {
    $log = Attendance::factory()->create([
        'date' => '2025-06-15',
        'clock_in' => '08:00:00',
        'clock_out' => '17:00:00',
        'is_verified' => true,
        'verified_at' => now(),
    ]);

    expect($log->date)->toBeInstanceOf(Carbon\Carbon::class)
        ->and($log->date->format('Y-m-d'))->toBe('2025-06-15')
        ->and($log->clock_in)->toBeInstanceOf(Carbon\Carbon::class)
        ->and($log->clock_out)->toBeInstanceOf(Carbon\Carbon::class)
        ->and($log->is_verified)->toBeTrue()
        ->and($log->verified_at)->toBeInstanceOf(Carbon\Carbon::class);
});

it('belongs to user', function () {
    $user = User::factory()->create();
    $log = Attendance::factory()->create(['user_id' => $user->id]);

    expect($log->user)->toBeInstanceOf(User::class)
        ->and($log->user->id)->toBe($user->id);
});

it('belongs to registration', function () {
    $registration = Registration::factory()->create();
    $log = Attendance::factory()->create(['registration_id' => $registration->id]);

    expect($log->registration)->toBeInstanceOf(Registration::class)
        ->and($log->registration->id)->toBe($registration->id);
});

it('belongs to verifier', function () {
    $verifier = User::factory()->create();
    $log = Attendance::factory()->create(['verified_by' => $verifier->id]);

    expect($log->verifier)->toBeInstanceOf(User::class)
        ->and($log->verifier->id)->toBe($verifier->id);
});

it('delegates status checks to entity', function () {
    $log = Attendance::factory()->create(['clock_out' => now()]);
    expect($log->asAttendanceStatus()->hasClockOut())->toBeTrue();

    $log->update(['clock_out' => null]);
    expect($log->asAttendanceStatus()->hasClockOut())->toBeFalse();
});
