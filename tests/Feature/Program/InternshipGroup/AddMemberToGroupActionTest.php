<?php

declare(strict_types=1);

use App\Enrollment\Registration\Models\Registration;
use App\Program\InternshipGroup\Actions\AddMemberToGroupAction;
use App\Program\InternshipGroup\Enums\InternshipGroupRole;
use App\Program\InternshipGroup\Models\InternshipGroup;
use App\Program\InternshipGroup\Models\InternshipGroupMember;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('adds student member to group', function () {
    $group = InternshipGroup::factory()->create();
    $registration = Registration::factory()->create();
    $action = app(AddMemberToGroupAction::class);

    $member = $action->execute($group, [
        'registration_id' => $registration->id,
        'role' => InternshipGroupRole::STUDENT->value,
    ]);

    expect($member)->toBeInstanceOf(InternshipGroupMember::class);
    expect((string) $member->registration_id)->toBe((string) $registration->id);
    expect($member->role)->toBe(InternshipGroupRole::STUDENT->value);
    expect($member->joined_at)->not->toBeNull();
    $this->assertDatabaseHas('internship_group_members', ['id' => $member->id]);
});

test('adds school teacher member to group', function () {
    $group = InternshipGroup::factory()->create();
    $action = app(AddMemberToGroupAction::class);

    $member = $action->execute($group, [
        'role' => InternshipGroupRole::SCHOOL_TEACHER->value,
    ]);

    expect($member->role)->toBe(InternshipGroupRole::SCHOOL_TEACHER->value);
});

test('adds industry supervisor member to group', function () {
    $group = InternshipGroup::factory()->create();
    $action = app(AddMemberToGroupAction::class);

    $member = $action->execute($group, [
        'role' => InternshipGroupRole::INDUSTRY_SUPERVISOR->value,
    ]);

    expect($member->role)->toBe(InternshipGroupRole::INDUSTRY_SUPERVISOR->value);
});

test('associates member with group', function () {
    $group = InternshipGroup::factory()->create();
    $action = app(AddMemberToGroupAction::class);

    $member = $action->execute($group, [
        'role' => InternshipGroupRole::STUDENT->value,
    ]);

    expect((string) $member->internship_group_id)->toBe((string) $group->id);
});
