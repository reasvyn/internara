<?php

declare(strict_types=1);

use App\Core\Exceptions\RejectedException;
use App\Program\InternshipGroup\Actions\DeleteInternshipGroupAction;
use App\Program\InternshipGroup\Models\InternshipGroup;
use App\Program\InternshipGroup\Models\InternshipGroupMember;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('deletes empty group', function () {
    $group = InternshipGroup::factory()->create();
    $action = app(DeleteInternshipGroupAction::class);

    $action->execute($group);

    $this->assertDatabaseMissing('internship_groups', ['id' => $group->id]);
});

test('rejects deletion when group has members', function () {
    $group = InternshipGroup::factory()->create();
    InternshipGroupMember::factory()->create([
        'internship_group_id' => $group->id,
    ]);
    $action = app(DeleteInternshipGroupAction::class);

    expect(fn () => $action->execute($group))->toThrow(RejectedException::class);

    $this->assertDatabaseHas('internship_groups', ['id' => $group->id]);
});
