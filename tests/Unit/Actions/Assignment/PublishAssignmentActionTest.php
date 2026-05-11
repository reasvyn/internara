<?php

declare(strict_types=1);

use App\Actions\Assignment\PublishAssignmentAction;
use Database\Factories\AssignmentFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('execute', function () {
    it('publishes a draft assignment', function () {
        $assignment = AssignmentFactory::new()->create(['status' => 'draft']);

        $result = app(PublishAssignmentAction::class)->execute($assignment);

        expect($result->status->value)->toBe('published');
    });

    it('throws if assignment is not in draft status', function () {
        $assignment = AssignmentFactory::new()->published()->create();

        expect(fn () => app(PublishAssignmentAction::class)->execute($assignment))
            ->toThrow(InvalidArgumentException::class, 'Only draft assignments can be published');
    });
});
