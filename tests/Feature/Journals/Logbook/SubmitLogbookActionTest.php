<?php

declare(strict_types=1);

use App\Core\Exceptions\RejectedException;
use App\Enrollment\Registration\Models\Registration;
use App\Journals\Logbook\Actions\SubmitLogbookAction;
use App\Journals\Logbook\Enums\LogbookStatus;
use App\Journals\Logbook\Models\Logbook;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;

uses(LazilyRefreshDatabase::class);

test('submits logbook entry for today', function () {
    $registration = Registration::factory()->create(['status' => 'active']);
    $user = User::factory()->create();
    $user->registrations()->save($registration);

    $entry = app(SubmitLogbookAction::class)->execute($user, [
        'content' => 'Today I worked on the API.',
        'learning_outcomes' => 'Understood REST principles.',
    ]);

    expect($entry)->toBeInstanceOf(Logbook::class);
    expect($entry->status)->toBe(LogbookStatus::SUBMITTED);
    expect($entry->date->toDateString())->toBe(now()->toDateString());
    expect($entry->registration_id)->toBe($registration->id);
    expect($entry->content)->toBe('Today I worked on the API.');
    expect($entry->learning_outcomes)->toBe('Understood REST principles.');
});

test('submits logbook entry without learning outcomes', function () {
    $registration = Registration::factory()->create(['status' => 'active']);
    $user = User::factory()->create();
    $user->registrations()->save($registration);

    $entry = app(SubmitLogbookAction::class)->execute($user, ['content' => 'Worked.']);

    expect($entry->learning_outcomes)->toBeNull();
});

test('updates existing draft entry when submitting again today', function () {
    $registration = Registration::factory()->create(['status' => 'active']);
    $user = User::factory()->create();
    $user->registrations()->save($registration);

    app(SubmitLogbookAction::class)->execute($user, ['content' => 'First submission.']);

    $updated = app(SubmitLogbookAction::class)->execute($user, [
        'content' => 'Updated submission.',
    ]);

    expect($updated->content)->toBe('Updated submission.');
    expect($updated->status)->toBe(LogbookStatus::SUBMITTED);
});

test('throws exception when user has no active registration', function () {
    $user = User::factory()->create();

    app(SubmitLogbookAction::class)->execute($user, ['content' => 'Should fail.']);
})->throws(RejectedException::class, 'No active internship registration found.');

test('throws exception when entry already submitted for today', function () {
    $registration = Registration::factory()->create(['status' => 'active']);
    $user = User::factory()->create();
    $user->registrations()->save($registration);

    app(SubmitLogbookAction::class)->execute($user, ['content' => 'First.']);

    app(SubmitLogbookAction::class)->execute($user, ['content' => 'Second.']);
})->throws(RejectedException::class, 'Journal entry for today has already been submitted.');

test('attaches photos when submitted with photo data', function () {
    $registration = Registration::factory()->create(['status' => 'active']);
    $user = User::factory()->create();
    $user->registrations()->save($registration);

    $photo = UploadedFile::fake()->image('work.jpg');

    $entry = app(SubmitLogbookAction::class)->execute($user, [
        'content' => 'Worked on site.',
        'photos' => [$photo],
    ]);

    expect($entry->getMedia('photos'))->toHaveCount(1);
});
