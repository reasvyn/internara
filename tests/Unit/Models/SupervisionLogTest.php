<?php

declare(strict_types=1);

use App\Enums\Mentor\SupervisionLogStatus;
use App\Models\Registration;
use App\Models\SupervisionLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can be created with factory', function () {
    $log = SupervisionLog::factory()->create();

    expect($log)->toBeInstanceOf(SupervisionLog::class)
        ->and($log->id)->toBeUuid();
});

it('casts attributes correctly', function () {
    $log = SupervisionLog::factory()->create([
        'date' => '2025-06-15',
        'is_verified' => true,
        'verified_at' => now(),
    ]);

    expect($log->date)->toBeInstanceOf(Carbon\Carbon::class)
        ->and($log->date->format('Y-m-d'))->toBe('2025-06-15')
        ->and($log->is_verified)->toBeTrue()
        ->and($log->verified_at)->toBeInstanceOf(Carbon\Carbon::class);
});

it('belongs to registration', function () {
    $registration = Registration::factory()->create();
    $log = SupervisionLog::factory()->create(['registration_id' => $registration->id]);

    expect($log->registration)->toBeInstanceOf(Registration::class)
        ->and($log->registration->id)->toBe($registration->id);
});

it('belongs to supervisor', function () {
    $supervisor = User::factory()->create();
    $log = SupervisionLog::factory()->create(['supervisor_id' => $supervisor->id]);

    expect($log->supervisor)->toBeInstanceOf(User::class)
        ->and($log->supervisor->id)->toBe($supervisor->id);
});

it('delegates status checks to entity', function () {
    $log = SupervisionLog::factory()->create(['status' => SupervisionLogStatus::COMPLETED]);
    expect($log->asSupervisionStatus()->isCompleted())->toBeTrue();

    $log->update(['status' => SupervisionLogStatus::PENDING]);
    expect($log->asSupervisionStatus()->isActive())->toBeTrue();
});
