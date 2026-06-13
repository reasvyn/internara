<?php

declare(strict_types=1);

use App\Assignment\Actions\DeleteAssignmentAction;
use App\Assignment\Models\Assignment;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('deletes assignment without submissions', function () {
    $assignment = Assignment::factory()->create();

    app(DeleteAssignmentAction::class)->execute($assignment);

    $this->assertModelMissing($assignment);
});
