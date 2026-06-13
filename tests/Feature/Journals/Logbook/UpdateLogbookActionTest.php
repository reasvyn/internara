<?php

declare(strict_types=1);

use App\Journals\Logbook\Actions\UpdateLogbookAction;
use App\Journals\Logbook\Enums\LogbookStatus;
use App\Journals\Logbook\Models\Logbook;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('updates logbook entry content', function () {
    $entry = Logbook::factory()->create();

    $updated = app(UpdateLogbookAction::class)->execute($entry, [
        'content' => 'Updated content.',
    ]);

    expect($updated->content)->toBe('Updated content.');
});

test('updates logbook entry learning outcomes', function () {
    $entry = Logbook::factory()->create(['learning_outcomes' => null]);

    $updated = app(UpdateLogbookAction::class)->execute($entry, [
        'learning_outcomes' => 'New outcomes.',
    ]);

    expect($updated->learning_outcomes)->toBe('New outcomes.');
});

test('updates logbook entry status', function () {
    $entry = Logbook::factory()->create(['status' => LogbookStatus::DRAFT]);

    $updated = app(UpdateLogbookAction::class)->execute($entry, [
        'status' => 'submitted',
    ]);

    expect($updated->status)->toBe(LogbookStatus::SUBMITTED);
});

test('updates verification when is_verified is true', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');
    $this->actingAs($admin);

    $entry = Logbook::factory()->create(['is_verified' => false]);

    $updated = app(UpdateLogbookAction::class)->execute($entry, [
        'is_verified' => true,
    ]);

    expect($updated->is_verified)->toBeTrue();
    expect($updated->verified_by)->toBe($admin->id);
    expect($updated->verified_at)->not->toBeNull();
});

test('updates supervisor fields', function () {
    $supervisor = User::factory()->create();

    $entry = Logbook::factory()->create();

    $updated = app(UpdateLogbookAction::class)->execute($entry, [
        'mentor_feedback' => 'Great work!',
        'supervisor_note' => 'Keep it up.',
        'supervisor_id' => $supervisor->id,
        'supervisor_reviewed_at' => now(),
    ]);

    expect($updated->mentor_feedback)->toBe('Great work!');
    expect($updated->supervisor_note)->toBe('Keep it up.');
    expect($updated->supervisor_id)->toBe($supervisor->id);
    expect($updated->supervisor_reviewed_at)->not->toBeNull();
});

test('does not change entry when no data is provided', function () {
    $entry = Logbook::factory()->create(['content' => 'Original content.']);

    app(UpdateLogbookAction::class)->execute($entry, []);

    expect($entry->fresh()->content)->toBe('Original content.');
});

test('partial update only changes provided fields', function () {
    $entry = Logbook::factory()->create([
        'content' => 'Original.',
        'learning_outcomes' => 'Original outcomes.',
    ]);

    app(UpdateLogbookAction::class)->execute($entry, [
        'content' => 'Updated only content.',
    ]);

    $fresh = $entry->fresh();
    expect($fresh->content)->toBe('Updated only content.');
    expect($fresh->learning_outcomes)->toBe('Original outcomes.');
});
