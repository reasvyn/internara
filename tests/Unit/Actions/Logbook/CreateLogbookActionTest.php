<?php

declare(strict_types=1);

use App\Actions\Logbook\CreateLogbookAction;
use App\Models\Logbook;
use Database\Factories\MenteeFactory;
use Database\Factories\RegistrationFactory;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('execute', function () {
    it('creates a logbook entry', function () {
        $user = UserFactory::new()->create();
        $mentee = MenteeFactory::new()->create(['user_id' => $user->id]);
        $registration = RegistrationFactory::new()->create(['mentee_id' => $mentee->id]);
        $registration->setStatus('active', 'Active');

        $entry = app(CreateLogbookAction::class)->execute($user->id, [
            'date' => '2026-05-01',
            'content' => 'Worked on project tasks.',
            'status' => 'draft',
        ]);

        expect($entry)->toBeInstanceOf(Logbook::class)
            ->and($entry->user_id)->toBe($user->id)
            ->and($entry->content)->toBe('Worked on project tasks.')
            ->and($entry->status->value)->toBe('draft');
    });
});
