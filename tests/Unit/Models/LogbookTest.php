<?php

declare(strict_types=1);

use App\Enums\Logbook\LogbookStatus;
use App\Models\Logbook;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can be created with factory', function () {
    $entry = Logbook::factory()->create();

    expect($entry)->toBeInstanceOf(Logbook::class)
        ->and($entry->id)->toBeUuid();
});

it('casts attributes correctly', function () {
    $entry = Logbook::factory()->create([
        'date' => '2025-06-15',
        'status' => LogbookStatus::VERIFIED,
        'is_verified' => true,
        'verified_at' => now(),
    ]);

    expect($entry->date)->toBeInstanceOf(Carbon\Carbon::class)
        ->and($entry->date->format('Y-m-d'))->toBe('2025-06-15')
        ->and($entry->status)->toBe(LogbookStatus::VERIFIED)
        ->and($entry->is_verified)->toBeTrue()
        ->and($entry->verified_at)->toBeInstanceOf(Carbon\Carbon::class);
});

it('belongs to user', function () {
    $user = User::factory()->create();
    $entry = Logbook::factory()->create(['user_id' => $user->id]);

    expect($entry->user)->toBeInstanceOf(User::class)
        ->and($entry->user->id)->toBe($user->id);
});

it('belongs to registration', function () {
    $registration = Registration::factory()->create();
    $entry = Logbook::factory()->create(['registration_id' => $registration->id]);

    expect($entry->registration)->toBeInstanceOf(Registration::class)
        ->and($entry->registration->id)->toBe($registration->id);
});

it('belongs to verifier', function () {
    $verifier = User::factory()->create();
    $entry = Logbook::factory()->create(['verified_by' => $verifier->id]);

    expect($entry->verifier)->toBeInstanceOf(User::class)
        ->and($entry->verifier->id)->toBe($verifier->id);
});

it('delegates status checks to entity', function () {
    $entry = Logbook::factory()->create(['status' => LogbookStatus::VERIFIED]);
    expect($entry->asLogbookState()->isVerified())->toBeTrue();

    $entry->update(['status' => LogbookStatus::DRAFT]);
    expect($entry->asLogbookState()->canBeEdited())->toBeTrue();
});
