<?php

declare(strict_types=1);

use App\Assessment\Rubric\Actions\UpdateRubricAction;
use App\Assessment\Rubric\Models\Rubric;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('updates rubric fields', function () {
    $rubric = Rubric::factory()->create();

    $updated = app(UpdateRubricAction::class)->execute(
        $rubric,
        name: 'Updated Rubric',
        description: 'Updated description',
        isActive: false,
    );

    expect($updated->name)->toBe('Updated Rubric');
    expect($updated->is_active)->toBeFalse();
});
