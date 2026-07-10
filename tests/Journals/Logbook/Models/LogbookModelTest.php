<?php

declare(strict_types=1);

use App\Enrollment\Registration\Models\Registration;
use App\Journals\Logbook\Entities\LogbookState;
use App\Journals\Logbook\Enums\LogbookStatus;
use App\Journals\Logbook\Models\Logbook;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;

uses(LazilyRefreshDatabase::class);

test('logbook factory creates valid model', function () {
    $entry = Logbook::factory()->create();

    expect($entry)->toBeInstanceOf(Logbook::class);
    expect($entry->user_id)->not->toBeNull();
    expect($entry->registration_id)->not->toBeNull();
    expect($entry->date)->not->toBeNull();
    expect($entry->content)->not->toBeNull();
});

test('logbook belongs to user', function () {
    $user = User::factory()->create();
    $entry = Logbook::factory()->create(['user_id' => $user->id]);

    expect($entry->user)->toBeInstanceOf(User::class);
    expect($entry->user->id)->toBe($user->id);
});

test('logbook belongs to registration', function () {
    $registration = Registration::factory()->create();
    $entry = Logbook::factory()->create(['registration_id' => $registration->id]);

    expect($entry->registration)->toBeInstanceOf(Registration::class);
    expect($entry->registration->id)->toBe($registration->id);
});

test('logbook casts status to enum', function () {
    $entry = Logbook::factory()->create(['status' => LogbookStatus::DRAFT]);

    expect($entry->status)->toBeInstanceOf(LogbookStatus::class);
    expect($entry->status)->toBe(LogbookStatus::DRAFT);
});

test('logbook casts date to date instance', function () {
    $entry = Logbook::factory()->create();

    expect($entry->date)->toBeInstanceOf(Carbon::class);
});

test('logbook casts is_verified to boolean', function () {
    $entry = Logbook::factory()->create(['is_verified' => true]);

    expect($entry->is_verified)->toBeTrue();
});

test('logbook casts verified_at and supervisor_reviewed_at to datetime', function () {
    $entry = Logbook::factory()->create([
        'verified_at' => now(),
        'supervisor_reviewed_at' => now(),
    ]);

    expect($entry->verified_at)->toBeInstanceOf(Carbon::class);
    expect($entry->supervisor_reviewed_at)->toBeInstanceOf(Carbon::class);
});

test('logbook returns LogbookState', function () {
    $entry = Logbook::factory()->create();

    $state = $entry->asLogbookState();

    expect($state)->toBeInstanceOf(LogbookState::class);
});

test('logbook registers media collection', function () {
    $entry = Logbook::factory()->create();

    expect($entry->hasMedia('photos'))->toBeFalse();
});

test('logbook fillable attributes are mass assignable', function () {
    $entry = Logbook::factory()->create([
        'mentor_feedback' => 'Good progress.',
        'supervisor_note' => 'Keep it up.',
        'supervisor_id' => User::factory()->create()->id,
    ]);

    expect($entry->mentor_feedback)->toBe('Good progress.');
    expect($entry->supervisor_note)->toBe('Keep it up.');
});

test('logbook uses LogbookFactory', function () {
    $entry = Logbook::factory()->create();

    expect($entry)->toBeInstanceOf(Logbook::class);
});
