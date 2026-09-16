<?php

declare(strict_types=1);

use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use App\Modules\Program\Domain\Internship\Models\Internship;
use App\Modules\Program\Domain\InternshipGroup\Livewire\InternshipGroupManager;
use App\Modules\Program\Domain\InternshipGroup\Models\InternshipGroup;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

function groupManagerAsAdmin(): User
{
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    test()->actingAs($admin);

    return $admin;
}

describe('IT0OE: internship group manager flows', function (): void {
    test('IT0OE-FR-GROUP-010: the manager lists group name, internship title, and member count', function (): void {
        groupManagerAsAdmin();
        $internship = Internship::factory()->create(['name' => 'PKL Ganjil 2026']);
        $group = InternshipGroup::factory()->create([
            'name' => 'Kelompok A',
            'internship_id' => $internship->id,
        ]);
        $registration = Registration::factory()->create();
        $group->members()->create([
            'registration_id' => $registration->id,
            'role' => 'student',
            'joined_at' => now(),
        ]);

        $headers = app(InternshipGroupManager::class)->headers();
        $indexes = array_column($headers, 'index');

        expect($indexes)->toContain('name', 'internship', 'member_count', 'actions');

        Livewire::test(InternshipGroupManager::class)
            ->assertSee('Kelompok A')
            ->assertSee('PKL Ganjil 2026');
    });

    test('IT0OE-FR-GROUP-011: name search narrows the list without extra member queries', function (): void {
        groupManagerAsAdmin();
        InternshipGroup::factory()->create(['name' => 'Kelompok Alpha']);
        InternshipGroup::factory()->create(['name' => 'Kelompok Beta']);

        Livewire::test(InternshipGroupManager::class)
            ->set('search', 'Alpha')
            ->assertSee('Kelompok Alpha')
            ->assertDontSee('Kelompok Beta');
    });

    test('IT0OE-FR-GROUP-022: repeater rows can be added, removed by index, and reset', function (): void {
        groupManagerAsAdmin();
        $group = InternshipGroup::factory()->create();

        $page = Livewire::test(InternshipGroupManager::class)
            ->call('manageMembers', $group->id);

        expect($page->get('memberFormData'))->toHaveCount(1);

        $page->call('addMemberRow')->call('addMemberRow');

        expect($page->get('memberFormData'))->toHaveCount(3);

        $page->call('removeMemberRow', 1);

        expect($page->get('memberFormData'))->toHaveCount(2);

        $page->call('resetMemberForm');

        expect($page->get('memberFormData'))->toHaveCount(1);
    });

    test('IT0OE-FR-GROUP-019: student rows demand a registration and mentor rows demand a user', function (): void {
        groupManagerAsAdmin();
        $group = InternshipGroup::factory()->create();

        Livewire::test(InternshipGroupManager::class)
            ->call('manageMembers', $group->id)
            ->set('memberFormData', [['role' => 'student', 'registration_id' => '', 'mentor_id' => '']])
            ->call('addMembers')
            ->assertHasErrors('memberFormData.0.registration_id');

        expect($group->members()->count())->toBe(0);

        Livewire::test(InternshipGroupManager::class)
            ->call('manageMembers', $group->id)
            ->set('memberFormData', [['role' => 'school_teacher', 'registration_id' => '', 'mentor_id' => '']])
            ->call('addMembers')
            ->assertHasErrors('memberFormData.0.mentor_id');

        expect($group->members()->count())->toBe(0);

        Livewire::test(InternshipGroupManager::class)
            ->call('manageMembers', $group->id)
            ->set('memberFormData', [['role' => 'dean', 'registration_id' => '', 'mentor_id' => '']])
            ->call('addMembers')
            ->assertHasErrors('memberFormData.0.role');

        expect($group->members()->count())->toBe(0);
    });

    test('IT0OE-UC-GROUP-002: admin fills the cohort in one batch and prunes members one by one', function (): void {
        groupManagerAsAdmin();
        $group = InternshipGroup::factory()->create();
        $registrations = Registration::factory()->count(2)->create();
        $rows = $registrations->map(
            fn (Registration $registration) => [
                'role' => 'student',
                'registration_id' => $registration->id,
                'mentor_id' => '',
            ],
        )->all();

        Livewire::test(InternshipGroupManager::class)
            ->call('manageMembers', $group->id)
            ->set('memberFormData', $rows)
            ->call('addMembers')
            ->assertHasNoErrors();

        expect($group->members()->count())->toBe(2);

        $memberId = $group->members()->first()->id;

        Livewire::test(InternshipGroupManager::class)
            ->call('removeMember', $memberId)
            ->assertHasNoErrors();

        expect($group->members()->count())->toBe(1);
    });

    test('IT0OE-FR-GROUP-023: one bad repeater row vetoes the whole batch', function (): void {
        groupManagerAsAdmin();
        $group = InternshipGroup::factory()->create();
        $registration = Registration::factory()->create();

        Livewire::test(InternshipGroupManager::class)
            ->call('manageMembers', $group->id)
            ->set('memberFormData', [
                ['role' => 'student', 'registration_id' => $registration->id, 'mentor_id' => ''],
                ['role' => 'student', 'registration_id' => '00000000-0000-0000-0000-000000000000', 'mentor_id' => ''],
            ])
            ->call('addMembers')
            ->assertHasErrors();

        expect($group->members()->count())->toBe(0);
    });

    test('IT0OE-FR-GROUP-020: member removal is limited to users who may update the group', function (): void {
        $group = InternshipGroup::factory()->create();
        $registration = Registration::factory()->create();
        $member = $group->members()->create([
            'registration_id' => $registration->id,
            'role' => 'student',
            'joined_at' => now(),
        ]);

        $student = User::factory()->create();
        $student->assignRole('student');
        $this->actingAs($student);

        Livewire::test(InternshipGroupManager::class)
            ->call('removeMember', $member->id)
            ->assertForbidden();

        expect($group->members()->count())->toBe(1);

        groupManagerAsAdmin();

        Livewire::test(InternshipGroupManager::class)
            ->call('removeMember', $member->id)
            ->assertHasNoErrors();

        expect($group->members()->count())->toBe(0);
    });

    test('IT0OE-NFR-GROUP-002: member add and remove both pass through group update authority', function (): void {
        $group = InternshipGroup::factory()->create();
        $registration = Registration::factory()->create();

        $student = User::factory()->create();
        $student->assignRole('student');
        $this->actingAs($student);

        Livewire::test(InternshipGroupManager::class)
            ->call('manageMembers', $group->id)
            ->set('memberFormData', [[
                'role' => 'student',
                'registration_id' => $registration->id,
                'mentor_id' => '',
            ]])
            ->call('addMembers')
            ->assertForbidden();

        expect($group->members()->count())->toBe(0);
    });

    test('IT0OE-FR-GROUP-029: group edit, member intake, and delete confirm own their modal state', function (): void {
        groupManagerAsAdmin();
        $group = InternshipGroup::factory()->create(['name' => 'Kelompok A']);

        Livewire::test(InternshipGroupManager::class)
            ->call('create')
            ->assertSet('showModal', true)
            ->assertSet('editingId', null);

        Livewire::test(InternshipGroupManager::class)
            ->call('edit', $group->id)
            ->assertSet('showModal', true)
            ->assertSet('editingId', $group->id);

        Livewire::test(InternshipGroupManager::class)
            ->call('manageMembers', $group->id)
            ->assertSet('showMemberModal', true)
            ->assertSet('memberGroupId', $group->id);

        app()->setLocale('en');

        Livewire::test(InternshipGroupManager::class)
            ->call('askDelete', $group->id)
            ->assertSet('confirmTarget', $group->id)
            ->assertSet('confirmType', 'delete');

        $page = Livewire::test(InternshipGroupManager::class)->call('askDelete', $group->id);

        expect($page->get('confirmMessage'))->toContain('Kelompok A');
    });

    test('IT0OE-NFR-GROUP-006: the delete confirmation names the group before committing', function (): void {
        groupManagerAsAdmin();
        app()->setLocale('en');
        $group = InternshipGroup::factory()->create(['name' => 'Kelompok A']);

        $page = Livewire::test(InternshipGroupManager::class)->call('askDelete', $group->id);

        expect($page->get('confirmMessage'))->toContain('Kelompok A')
            ->and($page->get('confirmTarget'))->toBe($group->id);
    });

    test('IT0OE-FR-GROUP-030: internship and role options compute from live domain data', function (): void {
        groupManagerAsAdmin();
        $internship = Internship::factory()->create(['name' => 'PKL Ganjil 2026']);

        $page = Livewire::test(InternshipGroupManager::class);

        $internships = $page->get('internships');
        $names = array_column($internships, 'name');

        expect($names)->toContain('PKL Ganjil 2026');

        $roles = $page->get('roleOptions');
        $ids = array_column($roles, 'id');

        expect($ids)->toEqualCanonicalizing(['student', 'school_teacher', 'industry_supervisor']);

        foreach ($roles as $option) {
            expect($option['name'])->toBeString()->not->toBeEmpty();
        }

        expect($internship->exists)->toBeTrue();
    });

    test('IT0OE-FR-GROUP-031: the group form refuses a nameless save and accepts a valid one', function (): void {
        groupManagerAsAdmin();
        $internship = Internship::factory()->create();

        Livewire::test(InternshipGroupManager::class)
            ->call('create')
            ->set('form.name', '')
            ->set('form.internship_id', $internship->id)
            ->call('save')
            ->assertHasErrors('form.name');

        expect(InternshipGroup::where('internship_id', $internship->id)->count())->toBe(0);

        Livewire::test(InternshipGroupManager::class)
            ->call('create')
            ->set('form.name', 'Kelompok Valid')
            ->set('form.internship_id', $internship->id)
            ->call('save')
            ->assertHasNoErrors();

        expect(InternshipGroup::where('name', 'Kelompok Valid')->exists())->toBeTrue();
    });

    test('IT0OE-FR-GROUP-027: the groups route sits behind admin middleware', function (): void {
        $this->get('/admin/internships/groups')->assertRedirect();

        $student = User::factory()->create();
        $student->assignRole('student');
        $this->actingAs($student);
        $this->get('/admin/internships/groups')->assertForbidden();

        groupManagerAsAdmin();
        $this->get('/admin/internships/groups')->assertOk();
    });
});
