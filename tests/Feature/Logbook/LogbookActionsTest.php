<?php

declare(strict_types=1);

use App\Domain\Auth\Enums\Role;
use App\Domain\Core\Exceptions\RejectedException;
use App\Domain\Logbook\Actions\CreateLogbookAction;
use App\Domain\Logbook\Actions\DeleteLogbookAction;
use App\Domain\Logbook\Actions\SubmitLogbookAction;
use App\Domain\Logbook\Actions\UpdateLogbookAction;
use App\Domain\Logbook\Models\Logbook;
use App\Domain\Mentee\Models\Mentee;
use App\Domain\Registration\Models\Registration;
use App\Domain\User\Models\User;
use Spatie\Permission\Models\Role as RoleModel;

beforeEach(function () {
    RoleModel::firstOrCreate(['name' => Role::STUDENT->value, 'guard_name' => 'web']);
    RoleModel::firstOrCreate(['name' => Role::SUPER_ADMIN->value, 'guard_name' => 'web']);
});

describe('CreateLogbookAction', function () {
    it('creates a logbook entry for a user with active registration', function () {
        $user = User::factory()->create();
        $user->assignRole(Role::STUDENT->value);
        $mentee = Mentee::factory()->create(['user_id' => $user->id]);
        $registration = Registration::factory()->create(['mentee_id' => $mentee->id]);
        $registration->setStatus('active');

        $entry = app(CreateLogbookAction::class)->execute($user->id, [
            'date' => now()->toDateString(),
            'content' => 'Worked on project tasks',
            'learning_outcomes' => 'Learned Laravel testing',
        ]);

        expect($entry)->toBeInstanceOf(Logbook::class)
            ->and($entry->user_id)->toBe($user->id)
            ->and($entry->content)->toBe('Worked on project tasks')
            ->and($entry->status->value)->toBe('draft');
    });

    it('throws when user has no active registration', function () {
        $user = User::factory()->create();
        $user->assignRole(Role::STUDENT->value);

        app(CreateLogbookAction::class)->execute($user->id, [
            'date' => now()->toDateString(),
            'content' => 'Test content',
        ]);
    })->throws(RejectedException::class, 'No active internship registration found.');
});

describe('UpdateLogbookAction', function () {
    it('updates a logbook entry', function () {
        $entry = Logbook::factory()->create();

        $updated = app(UpdateLogbookAction::class)->execute($entry, [
            'content' => 'Updated content',
            'learning_outcomes' => 'Updated outcomes',
        ]);

        expect($updated->content)->toBe('Updated content')
            ->and($updated->learning_outcomes)->toBe('Updated outcomes');
    });

    it('updates mentor feedback', function () {
        $entry = Logbook::factory()->create();
        $admin = User::factory()->create();
        $admin->assignRole(Role::SUPER_ADMIN->value);
        $this->actingAs($admin);

        $updated = app(UpdateLogbookAction::class)->execute($entry, [
            'mentor_feedback' => 'Good work!',
        ]);

        expect($updated->mentor_feedback)->toBe('Good work!');
    });
});

describe('DeleteLogbookAction', function () {
    it('deletes a logbook entry', function () {
        $entry = Logbook::factory()->create();

        app(DeleteLogbookAction::class)->execute($entry);

        expect(Logbook::find($entry->id))->toBeNull();
    });
});

describe('SubmitLogbookAction', function () {
    it('submits a new daily journal entry', function () {
        $user = User::factory()->create();
        $user->assignRole(Role::STUDENT->value);
        $mentee = Mentee::factory()->create(['user_id' => $user->id]);
        $registration = Registration::factory()->create(['mentee_id' => $mentee->id]);
        $registration->setStatus('active');

        $journal = app(SubmitLogbookAction::class)->execute($user, [
            'content' => 'Today I worked on the internship project',
        ]);

        expect($journal)->toBeInstanceOf(Logbook::class)
            ->and($journal->status->value)->toBe('submitted')
            ->and($journal->user_id)->toBe($user->id);
    });

    it('throws when no active registration exists', function () {
        $user = User::factory()->create();

        app(SubmitLogbookAction::class)->execute($user, [
            'content' => 'Test entry',
        ]);
    })->throws(RejectedException::class, 'No active internship registration found.');

    it('throws when entry for today already submitted', function () {
        $user = User::factory()->create();
        $user->assignRole(Role::STUDENT->value);
        $mentee = Mentee::factory()->create(['user_id' => $user->id]);
        $registration = Registration::factory()->create(['mentee_id' => $mentee->id]);
        $registration->setStatus('active');

        app(SubmitLogbookAction::class)->execute($user, ['content' => 'First entry']);

        app(SubmitLogbookAction::class)->execute($user, ['content' => 'Second entry']);
    })->throws(RejectedException::class, 'already been submitted');
});
