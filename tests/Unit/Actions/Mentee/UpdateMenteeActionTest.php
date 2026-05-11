<?php

declare(strict_types=1);

use App\Actions\Mentee\UpdateMenteeAction;
use Database\Factories\MenteeFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('execute', function () {
    it('updates a mentee record', function () {
        $mentee = MenteeFactory::new()->create();

        $result = app(UpdateMenteeAction::class)->execute($mentee, ['internal_notes' => 'Excellent progress']);

        expect($result->internal_notes)->toBe('Excellent progress');
    });
});
