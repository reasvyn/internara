<?php

declare(strict_types=1);

use App\Models\Schedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can be created with factory', function () {
    $schedule = Schedule::factory()->create();

    expect($schedule)->toBeInstanceOf(Schedule::class)
        ->and($schedule->id)->toBeUuid();
});

it('casts attributes correctly', function () {
    $schedule = Schedule::factory()->create([
        'start_at' => now()->addHour(),
        'end_at' => now()->addHours(2),
    ]);

    expect($schedule->start_at)->toBeInstanceOf(Carbon\Carbon::class)
        ->and($schedule->end_at)->toBeInstanceOf(Carbon\Carbon::class);
});

it('belongs to creator', function () {
    $creator = User::factory()->create();
    $schedule = Schedule::factory()->create(['created_by' => $creator->id]);

    expect($schedule->creator)->toBeInstanceOf(User::class)
        ->and($schedule->creator->id)->toBe($creator->id);
});

it('delegates timing checks to entity', function () {
    $schedule = Schedule::factory()->create([
        'start_at' => now()->subHour(),
        'end_at' => now()->addHour(),
    ]);

    expect($schedule->asScheduleStatus()->isOngoing())->toBeTrue();

    $schedule->update([
        'start_at' => now()->addHour(),
        'end_at' => now()->addHours(2),
    ]);
    expect($schedule->asScheduleStatus()->isUpcoming())->toBeTrue();
});
