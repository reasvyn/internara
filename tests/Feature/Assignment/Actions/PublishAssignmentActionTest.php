<?php

declare(strict_types=1);

use App\Assignment\Actions\PublishAssignmentAction;
use App\Assignment\Enums\AssignmentStatus;
use App\Assignment\Models\Assignment;
use App\Core\Exceptions\RejectedException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('publishes draft assignment', function () {
    $assignment = Assignment::factory()->create(['status' => AssignmentStatus::DRAFT->value]);

    $result = app(PublishAssignmentAction::class)->execute($assignment);

    expect($result->status->value)->toBe('published');
});

test('throws when publishing non-draft assignment', function () {
    $assignment = Assignment::factory()->create(['status' => AssignmentStatus::PUBLISHED->value]);

    app(PublishAssignmentAction::class)->execute($assignment);
})->throws(RejectedException::class, 'Only draft assignments can be published.');
