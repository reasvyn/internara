<?php

declare(strict_types=1);

use App\Program\Internship\Models\Internship;
use App\Program\InternshipGroup\Entities\InternshipGroupState;
use App\Program\InternshipGroup\Models\InternshipGroup;
use App\Program\InternshipGroup\Models\InternshipGroupMember;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('can create internship group using factory', function () {
    $group = InternshipGroup::factory()->create();

    expect($group)->toBeInstanceOf(InternshipGroup::class);
    $this->assertDatabaseHas('internship_groups', ['id' => $group->id]);
});

test('belongs to internship', function () {
    $internship = Internship::factory()->create();
    $group = InternshipGroup::factory()->create(['internship_id' => $internship->id]);

    expect($group->internship)->toBeInstanceOf(Internship::class);
    expect((string) $group->internship->id)->toBe((string) $internship->id);
});

test('has many members', function () {
    $group = InternshipGroup::factory()->create();
    InternshipGroupMember::factory()
        ->count(3)
        ->create(['internship_group_id' => $group->id]);

    expect($group->members)->toHaveCount(3);
});

test('casts is active to boolean', function () {
    $group = InternshipGroup::factory()->create(['is_active' => true]);

    expect($group->is_active)->toBeTrue();
});

test('as group state returns state entity', function () {
    $group = InternshipGroup::factory()->create();

    expect($group->asInternshipGroupState())
        ->toBeInstanceOf(InternshipGroupState::class);
});

test('uses uuid as primary key', function () {
    $group = InternshipGroup::factory()->create();

    expect($group->id)->toBeUuid();
});
