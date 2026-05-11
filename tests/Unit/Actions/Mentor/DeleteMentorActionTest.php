<?php

declare(strict_types=1);

use App\Actions\Mentor\DeleteMentorAction;
use App\Models\User;
use Database\Factories\MentorFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('execute', function () {
    it('deletes mentor and cascades to user', function () {
        $mentor = MentorFactory::new()->create();
        $userId = $mentor->user_id;

        app(DeleteMentorAction::class)->execute($mentor);

        expect($mentor->fresh())->toBeNull()
            ->and(User::find($userId))->toBeNull();
    });
});
