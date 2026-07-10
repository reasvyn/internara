<?php

declare(strict_types=1);

use App\Program\InternshipGroup\Actions\UpdateInternshipGroupAction;
use App\Program\InternshipGroup\Models\InternshipGroup;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('updates internship group name', function () {
    $group = InternshipGroup::factory()->create();
    $action = app(UpdateInternshipGroupAction::class);

    $updated = $action->execute($group, ['name' => 'Updated Group']);

    expect($updated->name)->toBe('Updated Group');
    $this->assertModelExists($group);
});

test('updates internship group description', function () {
    $group = InternshipGroup::factory()->create();
    $action = app(UpdateInternshipGroupAction::class);

    $updated = $action->execute($group, ['description' => 'New description']);

    expect($updated->description)->toBe('New description');
});

test('updates internship group active status', function () {
    $group = InternshipGroup::factory()->create(['is_active' => true]);
    $action = app(UpdateInternshipGroupAction::class);

    $updated = $action->execute($group, ['is_active' => false]);

    expect($updated->is_active)->toBeFalse();
});
