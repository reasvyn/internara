<?php

declare(strict_types=1);

use App\Actions\Mentee\DeleteMenteeAction;
use App\Models\User;
use Database\Factories\MenteeFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('execute', function () {
    it('deletes mentee and cascades to user', function () {
        $mentee = MenteeFactory::new()->create();
        $userId = $mentee->user_id;

        app(DeleteMenteeAction::class)->execute($mentee);

        expect($mentee->fresh())->toBeNull()
            ->and(User::find($userId))->toBeNull();
    });
});
