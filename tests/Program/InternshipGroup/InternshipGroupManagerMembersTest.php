<?php

declare(strict_types=1);

use App\Enrollment\Registration\Models\Registration;
use App\Program\InternshipGroup\Livewire\InternshipGroupManager;
use App\Program\InternshipGroup\Models\InternshipGroup;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

it('FR-MM10: manageMembers() initializes a single empty member row', function () {
    actingAsAdmin();
    $group = InternshipGroup::factory()->create();

    Livewire::test(InternshipGroupManager::class)
        ->call('manageMembers', $group->id)
        ->assertSet('showMemberModal', true)
        ->assertCount('memberFormData', 1)
        ->assertSet('memberFormData.0.role', 'student');
});

it('FR-MM10: addMemberRow() appends a new empty row', function () {
    actingAsAdmin();

    Livewire::test(InternshipGroupManager::class)
        ->call('addMemberRow')
        ->assertCount('memberFormData', 1);
});

it('FR-MM10: removeMemberRow() removes a row by index and reindexes', function () {
    actingAsAdmin();

    Livewire::test(InternshipGroupManager::class)
        ->call('addMemberRow')
        ->call('addMemberRow')
        ->call('removeMemberRow', 0)
        ->assertCount('memberFormData', 1);
});

it('FR-MM11: addMembers() creates the whole batch when all rows are valid', function () {
    actingAsAdmin();
    $group = InternshipGroup::factory()->create();
    $registrations = Registration::factory()->count(3)->create();

    Livewire::test(InternshipGroupManager::class)
        ->call('manageMembers', $group->id)
        ->call('addMemberRow')
        ->call('addMemberRow')
        ->set('memberFormData.0.registration_id', $registrations[0]->id)
        ->set('memberFormData.1.registration_id', $registrations[1]->id)
        ->set('memberFormData.2.registration_id', $registrations[2]->id)
        ->call('addMembers')
        ->assertHasNoErrors()
        ->assertSet('showMemberModal', false);

    expect($group->members()->count())->toBe(3);
});

it('FR-MM11: addMembers() blocks the whole batch when one row is invalid (all-or-nothing)', function () {
    actingAsAdmin();
    $group = InternshipGroup::factory()->create();
    $registration = Registration::factory()->create();

    Livewire::test(InternshipGroupManager::class)
        ->call('manageMembers', $group->id)
        ->call('addMemberRow')
        ->set('memberFormData.0.registration_id', $registration->id)
        ->set('memberFormData.1.registration_id', 'non-existent-id')
        ->call('addMembers')
        ->assertHasErrors(['memberFormData.1.registration_id'])
        ->assertSet('showMemberModal', true);

    expect($group->members()->count())->toBe(0);
});

it('FR-MM11: addMembers() rejects a batch with no rows', function () {
    actingAsAdmin();

    Livewire::test(InternshipGroupManager::class)
        ->call('manageMembers', InternshipGroup::factory()->create()->id)
        ->call('removeMemberRow', 0)
        ->call('addMembers')
        ->assertHasErrors(['memberFormData']);
});

it('FR-MM11: addMembers() enforces role-based fields per row (mentor required for teacher)', function () {
    actingAsAdmin();
    $group = InternshipGroup::factory()->create();

    Livewire::test(InternshipGroupManager::class)
        ->call('manageMembers', $group->id)
        ->set('memberFormData.0.role', 'school_teacher')
        ->call('addMembers')
        ->assertHasErrors(['memberFormData.0.mentor_id']);

    expect($group->members()->count())->toBe(0);
});
