<?php

declare(strict_types=1);

use App\Actions\Logbook\SubmitLogbookAction;
use App\Models\Logbook;
use App\Models\Registration;
use App\Models\User;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::create(['name' => 'super_admin', 'guard_name' => 'web']);
    Role::create(['name' => 'admin', 'guard_name' => 'web']);
    Role::create(['name' => 'teacher', 'guard_name' => 'web']);
    Role::create(['name' => 'student', 'guard_name' => 'web']);
    Role::create(['name' => 'supervisor', 'guard_name' => 'web']);
});

describe('SubmitLogbookAction', function () {
    beforeEach(function () {
        $this->student = User::factory()->create();
        $this->actingAs($this->student);

        $this->registration = Registration::factory()->create([
            'student_id' => $this->student->id,
        ]);
        $this->registration->setStatus('active');
    });

    it('submits a journal entry for today', function () {
        $journal = app(SubmitLogbookAction::class)->execute(
            user: $this->student,
            data: [
                'content' => 'Today I worked on the API integration.',
                'learning_outcomes' => 'Learned about RESTful APIs.',
            ],
        );

        expect($journal)->toBeInstanceOf(Logbook::class);
        expect($journal->id)->toBeUuid();
        expect($journal->user_id)->toBe($this->student->id);
        expect($journal->registration_id)->toBe($this->registration->id);
        expect($journal->date->toDateString())->toBe(now()->toDateString());
        expect($journal->content)->toBe('Today I worked on the API integration.');
        expect($journal->learning_outcomes)->toBe('Learned about RESTful APIs.');
        expect($journal->status->value)->toBe('submitted');
    });

    it('defaults learning outcomes to null', function () {
        $journal = app(SubmitLogbookAction::class)->execute(
            user: $this->student,
            data: [
                'content' => 'Worked on frontend tasks.',
            ],
        );

        expect($journal->learning_outcomes)->toBeNull();
    });

    it('throws when no active registration exists', function () {
        $studentWithoutRegistration = User::factory()->create();

        app(SubmitLogbookAction::class)->execute(
            user: $studentWithoutRegistration,
            data: ['content' => 'Some content.'],
        );
    })->throws(RuntimeException::class, 'No active internship registration found.');

    it('throws when a submitted entry already exists for today', function () {
        app(SubmitLogbookAction::class)->execute(
            user: $this->student,
            data: ['content' => 'First entry.'],
        );

        app(SubmitLogbookAction::class)->execute(
            user: $this->student,
            data: ['content' => 'Duplicate entry.'],
        );
    })->throws(RuntimeException::class, 'Journal entry for today has already been submitted.');

    it('creates a new entry when no draft exists', function () {
        $journal = app(SubmitLogbookAction::class)->execute(
            user: $this->student,
            data: [
                'content' => 'Fresh entry.',
            ],
        );

        expect($journal->content)->toBe('Fresh entry.');
        expect($journal->status->value)->toBe('submitted');
    });

    it('persists to database', function () {
        $journal = app(SubmitLogbookAction::class)->execute(
            user: $this->student,
            data: ['content' => 'Persistent entry.'],
        );

        expect(Logbook::find($journal->id))->not->toBeNull();
    });

    it('creates activity log', function () {
        $journal = app(SubmitLogbookAction::class)->execute(
            user: $this->student,
            data: ['content' => 'Audited entry.'],
        );

        $activity = Activity::where('event', 'journal_submitted')->first();
        expect($activity)->not->toBeNull();
        expect($activity->causer_id)->toBe($this->student->id);
        expect($activity->subject_id)->toBe($journal->id);
        expect($activity->properties['payload']['date'])->toContain(now()->toDateString());
    });
});
