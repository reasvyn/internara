<?php

declare(strict_types=1);

use App\Program\InternshipGroup\Actions\RemoveMemberFromGroupAction;
use App\Program\InternshipGroup\Models\InternshipGroupMember;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('removes member from group', function () {
    $member = InternshipGroupMember::factory()->create();
    $action = app(RemoveMemberFromGroupAction::class);

    $action->execute($member);

    $this->assertDatabaseMissing('internship_group_members', ['id' => $member->id]);
});
