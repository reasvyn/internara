<?php

declare(strict_types=1);

use App\Actions\Logbook\SubmitLogbookAction;
use App\Models\Logbook;
use Database\Factories\RegistrationFactory;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::create(['name' => 'student', 'guard_name' => 'web']);
});

describe('execute', function () {
    it('submits a journal for today', function () {
        $student = UserFactory::new()->create()->assignRole('student');
        $registration = RegistrationFactory::new()->create();
        $registration->setStatus('active', 'Active');

        $user = $registration->mentee->user;
        $journal = app(SubmitLogbookAction::class)->execute($user, [
            'content' => 'Today I learned about APIs.',
        ]);

        expect($journal)->toBeInstanceOf(Logbook::class)
            ->and($journal->status->value)->toBe('submitted')
            ->and($journal->content)->toBe('Today I learned about APIs.');
    });

    it('throws RuntimeException if no active registration', function () {
        $user = UserFactory::new()->create()->assignRole('student');

        expect(fn () => app(SubmitLogbookAction::class)->execute($user, [
            'content' => 'My journal entry.',
        ]))->toThrow(RuntimeException::class, 'No active internship registration found.');
    });

    it('throws RuntimeException for duplicate submission today', function () {
        $student = UserFactory::new()->create()->assignRole('student');
        $registration = RegistrationFactory::new()->create();
        $registration->setStatus('active', 'Active');

        $user = $registration->mentee->user;
        app(SubmitLogbookAction::class)->execute($user, ['content' => 'First entry.']);

        expect(fn () => app(SubmitLogbookAction::class)->execute($user, ['content' => 'Second entry.']))
            ->toThrow(RuntimeException::class, 'Journal entry for today has already been submitted.');
    });
});
