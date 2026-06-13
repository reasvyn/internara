<?php

declare(strict_types=1);

use App\Enrollment\Registration\Models\Registration;
use App\Program\InternshipGroup\Models\InternshipGroup;
use App\Program\InternshipGroup\Models\InternshipGroupMember;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('can create group member using factory', function () {
    $member = InternshipGroupMember::factory()->create();

    expect($member)->toBeInstanceOf(InternshipGroupMember::class);
    $this->assertDatabaseHas('internship_group_members', ['id' => $member->id]);
});

test('belongs to group', function () {
    $group = InternshipGroup::factory()->create();
    $member = InternshipGroupMember::factory()->create(['internship_group_id' => $group->id]);

    expect($member->group)->toBeInstanceOf(InternshipGroup::class);
    expect((string) $member->group->id)->toBe((string) $group->id);
});

test('belongs to registration', function () {
    $registration = Registration::factory()->create();
    $member = InternshipGroupMember::factory()->create(['registration_id' => $registration->id]);

    expect($member->registration)->toBeInstanceOf(Registration::class);
});

test('casts joined at to datetime', function () {
    $member = InternshipGroupMember::factory()->create();

    expect($member->joined_at)->toBeInstanceOf(Carbon::class);
});

test('uses uuid as primary key', function () {
    $member = InternshipGroupMember::factory()->create();

    expect($member->id)->toBeUuid();
});
