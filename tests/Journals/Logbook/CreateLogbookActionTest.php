<?php

declare(strict_types=1);

use App\Core\Exceptions\RejectedException;
use App\Enrollment\Registration\Models\Registration;
use App\Journals\Logbook\Actions\CreateLogbookAction;
use App\Journals\Logbook\Enums\LogbookStatus;
use App\Journals\Logbook\Models\Logbook;
use App\User\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('creates logbook entry with valid data', function () {
    $user = User::factory()->create();
    Registration::factory()->create([
        'student_id' => $user->id,
        'status' => 'active',
    ]);

    $entry = app(CreateLogbookAction::class)->execute($user->id, [
        'date' => now()->toDateString(),
        'content' => 'Worked on project tasks today.',
        'learning_outcomes' => 'Learned Laravel testing.',
    ]);

    expect($entry)->toBeInstanceOf(Logbook::class);
    $this->assertModelExists($entry);
    expect($entry->content)->toBe('Worked on project tasks today.');
    expect($entry->learning_outcomes)->toBe('Learned Laravel testing.');
    expect($entry->status)->toBe(LogbookStatus::DRAFT);
    expect($entry->is_verified)->toBeFalse();
});

test('creates logbook entry without learning outcomes', function () {
    $user = User::factory()->create();
    Registration::factory()->create([
        'student_id' => $user->id,
        'status' => 'active',
    ]);

    $entry = app(CreateLogbookAction::class)->execute($user->id, [
        'date' => now()->toDateString(),
        'content' => 'Worked on project.',
    ]);

    expect($entry->learning_outcomes)->toBeNull();
});

test('creates logbook entry with submitted status', function () {
    $user = User::factory()->create();
    Registration::factory()->create([
        'student_id' => $user->id,
        'status' => 'active',
    ]);

    $entry = app(CreateLogbookAction::class)->execute($user->id, [
        'date' => now()->toDateString(),
        'content' => 'Worked on project.',
        'status' => 'submitted',
    ]);

    expect($entry->status)->toBe(LogbookStatus::SUBMITTED);
});

test('creates logbook entry with verification when authenticated', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');
    $this->actingAs($admin);

    $user = User::factory()->create();
    Registration::factory()->create([
        'student_id' => $user->id,
        'status' => 'active',
    ]);

    $entry = app(CreateLogbookAction::class)->execute($user->id, [
        'date' => now()->toDateString(),
        'content' => 'Verified entry.',
        'is_verified' => true,
    ]);

    expect($entry->is_verified)->toBeTrue();
    expect($entry->verified_by)->toBe($admin->id);
    expect($entry->verified_at)->not->toBeNull();
});

test('throws exception when user has no active registration', function () {
    $user = User::factory()->create();

    app(CreateLogbookAction::class)->execute($user->id, [
        'date' => now()->toDateString(),
        'content' => 'Should fail.',
    ]);
})->throws(RejectedException::class, 'No active internship registration found.');

test('throws exception for non-existent user', function () {
    app(CreateLogbookAction::class)->execute('non-existent-id', [
        'date' => now()->toDateString(),
        'content' => 'Should fail.',
    ]);
})->throws(ModelNotFoundException::class);
