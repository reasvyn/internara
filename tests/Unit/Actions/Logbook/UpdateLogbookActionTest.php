<?php

declare(strict_types=1);

use App\Actions\Logbook\UpdateLogbookAction;
use Database\Factories\LogbookFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('execute', function () {
    it('updates a logbook entry', function () {
        $entry = LogbookFactory::new()->create();

        $result = app(UpdateLogbookAction::class)->execute($entry, [
            'content' => 'Updated content.',
            'mentor_feedback' => 'Great work!',
        ]);

        expect($result->content)->toBe('Updated content.')
            ->and($result->mentor_feedback)->toBe('Great work!');
    });
});
