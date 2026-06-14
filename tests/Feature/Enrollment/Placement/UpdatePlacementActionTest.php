<?php

declare(strict_types=1);

use App\Enrollment\Placement\Actions\UpdatePlacementAction;
use App\Enrollment\Placement\Models\Placement;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('updates placement name and quota', function () {
    $placement = Placement::factory()->create(['name' => 'Old Name', 'quota' => 5]);

    $result = app(UpdatePlacementAction::class)->execute($placement, [
        'name' => 'Updated Name',
        'quota' => 20,
    ]);

    expect($result->name)->toBe('Updated Name');
    expect($result->quota)->toBe(20);
});

test('updates placement description only', function () {
    $placement = Placement::factory()->create(['description' => 'Old description']);

    $result = app(UpdatePlacementAction::class)->execute($placement, [
        'description' => 'New description',
    ]);

    expect($result->description)->toBe('New description');
    $this->assertModelExists($placement);
});
