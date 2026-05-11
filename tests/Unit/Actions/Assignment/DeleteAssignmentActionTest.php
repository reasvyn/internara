<?php

declare(strict_types=1);

use App\Actions\Assignment\DeleteAssignmentAction;
use App\Models\Assignment;
use Database\Factories\AssignmentFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('execute', function () {
    it('deletes an assignment', function () {
        $assignment = AssignmentFactory::new()->create();
        $assignmentId = $assignment->id;

        app(DeleteAssignmentAction::class)->execute($assignment);

        expect(Assignment::find($assignmentId))->toBeNull();
    });
});
