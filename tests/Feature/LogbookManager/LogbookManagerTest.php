<?php

declare(strict_types=1);

use App\Actions\Logbook\CreateLogbookAction;
use App\Actions\Logbook\DeleteLogbookAction;
use App\Actions\Logbook\UpdateLogbookAction;
use App\Enums\Logbook\LogbookStatus;
use App\Models\Logbook;
use App\Models\Registration;
use App\Models\User;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::create(['name' => 'super_admin', 'guard_name' => 'web']);
    Role::create(['name' => 'admin', 'guard_name' => 'web']);
    Role::create(['name' => 'teacher', 'guard_name' => 'web']);
    Role::create(['name' => 'student', 'guard_name' => 'web']);
    Role::create(['name' => 'supervisor', 'guard_name' => 'web']);
});

describe('CreateLogbookAction', function () {
    beforeEach(function () {
        $this->actor = User::factory()->create();
        $this->actingAs($this->actor);

        $this->registration = Registration::factory()->create();
        $this->student = User::factory()->create();
    });

    it('creates logbook entry from input', function () {
        $entry = app(CreateLogbookAction::class)->execute(
            user: $this->student,
            data: [
                'registration_id' => $this->registration->id,
                'date' => '2025-07-01',
                'content' => 'Today I worked on the project.',
            ],
        );

        expect($entry)->toBeInstanceOf(Logbook::class);
        expect($entry->id)->toBeUuid();
        expect($entry->user_id)->toBe($this->student->id);
        expect($entry->registration_id)->toBe($this->registration->id);
        expect($entry->date->format('Y-m-d'))->toBe('2025-07-01');
        expect($entry->content)->toBe('Today I worked on the project.');
    });

    it('defaults status to draft', function () {
        $entry = app(CreateLogbookAction::class)->execute(
            user: $this->student,
            data: [
                'registration_id' => $this->registration->id,
                'date' => '2025-07-01',
                'content' => 'Journal content.',
            ],
        );

        expect($entry->status)->toBe(LogbookStatus::DRAFT);
    });

    it('accepts explicit status', function () {
        $entry = app(CreateLogbookAction::class)->execute(
            user: $this->student,
            data: [
                'registration_id' => $this->registration->id,
                'date' => '2025-07-01',
                'content' => 'Submitted journal.',
                'status' => LogbookStatus::SUBMITTED->value,
            ],
        );

        expect($entry->status)->toBe(LogbookStatus::SUBMITTED);
    });

    it('accepts learning outcomes', function () {
        $entry = app(CreateLogbookAction::class)->execute(
            user: $this->student,
            data: [
                'registration_id' => $this->registration->id,
                'date' => '2025-07-01',
                'content' => 'Journal content.',
                'learning_outcomes' => 'Learned about MVC.',
            ],
        );

        expect($entry->learning_outcomes)->toBe('Learned about MVC.');
    });

    it('defaults is_verified to false', function () {
        $entry = app(CreateLogbookAction::class)->execute(
            user: $this->student,
            data: [
                'registration_id' => $this->registration->id,
                'date' => '2025-07-01',
                'content' => 'Journal content.',
            ],
        );

        expect($entry->is_verified)->toBeFalse();
    });

    it('sets verified_by when is_verified is true', function () {
        $entry = app(CreateLogbookAction::class)->execute(
            user: $this->student,
            data: [
                'registration_id' => $this->registration->id,
                'date' => '2025-07-01',
                'content' => 'Verified journal.',
                'is_verified' => true,
            ],
        );

        expect($entry->is_verified)->toBeTrue();
        expect($entry->verified_by)->toBe($this->actor->id);
        expect($entry->verified_at)->not->toBeNull();
    });

    it('persists to database', function () {
        $entry = app(CreateLogbookAction::class)->execute(
            user: $this->student,
            data: [
                'registration_id' => $this->registration->id,
                'date' => '2025-07-01',
                'content' => 'Persistent journal.',
            ],
        );

        expect(Logbook::find($entry->id))->not->toBeNull();
    });

    it('creates activity log', function () {
        $entry = app(CreateLogbookAction::class)->execute(
            user: $this->student,
            data: [
                'registration_id' => $this->registration->id,
                'date' => '2025-07-01',
                'content' => 'Audited journal.',
            ],
        );

        $activity = Activity::where('event', 'logbook_entry_created')->first();
        expect($activity)->not->toBeNull();
        expect($activity->causer_id)->toBe($this->actor->id);
        expect($activity->subject_id)->toBe($entry->id);
    });
});

describe('UpdateLogbookAction', function () {
    beforeEach(function () {
        $this->actor = User::factory()->create();
        $this->actingAs($this->actor);
    });

    it('updates content', function () {
        $entry = Logbook::factory()->create();

        $result = app(UpdateLogbookAction::class)->execute($entry, [
            'content' => 'Updated journal content.',
        ]);

        expect($result->fresh()->content)->toBe('Updated journal content.');
    });

    it('does not change fields not in update data', function () {
        $entry = Logbook::factory()->create([
            'content' => 'Original content.',
            'learning_outcomes' => 'Original outcomes.',
        ]);

        app(UpdateLogbookAction::class)->execute($entry, [
            'content' => 'Updated content.',
        ]);

        expect($entry->fresh()->content)->toBe('Updated content.');
        expect($entry->fresh()->learning_outcomes)->toBe('Original outcomes.');
    });

    it('updates status', function () {
        $entry = Logbook::factory()->create([
            'status' => LogbookStatus::DRAFT,
        ]);

        app(UpdateLogbookAction::class)->execute($entry, [
            'status' => LogbookStatus::SUBMITTED->value,
        ]);

        expect($entry->fresh()->status)->toBe(LogbookStatus::SUBMITTED);
    });

    it('sets verified_by on verification', function () {
        $entry = Logbook::factory()->create(['is_verified' => false]);

        app(UpdateLogbookAction::class)->execute($entry, [
            'is_verified' => true,
        ]);

        expect($entry->fresh()->is_verified)->toBeTrue();
        expect($entry->fresh()->verified_by)->toBe($this->actor->id);
    });

    it('updates mentor feedback', function () {
        $entry = Logbook::factory()->create();

        app(UpdateLogbookAction::class)->execute($entry, [
            'mentor_feedback' => 'Great work!',
        ]);

        expect($entry->fresh()->mentor_feedback)->toBe('Great work!');
    });

    it('returns the same instance', function () {
        $entry = Logbook::factory()->create();

        $result = app(UpdateLogbookAction::class)->execute($entry, [
            'content' => 'Updated content.',
        ]);

        expect($result->id)->toBe($entry->id);
    });

    it('creates activity log on update', function () {
        $entry = Logbook::factory()->create();

        app(UpdateLogbookAction::class)->execute($entry, [
            'content' => 'Logged update.',
        ]);

        $activity = Activity::where('event', 'logbook_entry_updated')->first();
        expect($activity)->not->toBeNull();
        expect($activity->causer_id)->toBe($this->actor->id);
        expect($activity->subject_id)->toBe($entry->id);
    });

    it('succeeds with no changed attributes', function () {
        $entry = Logbook::factory()->create();

        $result = app(UpdateLogbookAction::class)->execute($entry, []);

        expect($result->id)->toBe($entry->id);
    });
});

describe('DeleteLogbookAction', function () {
    beforeEach(function () {
        $this->actor = User::factory()->create();
        $this->actingAs($this->actor);
    });

    it('deletes logbook entry from database', function () {
        $entry = Logbook::factory()->create();
        $entryId = $entry->id;

        app(DeleteLogbookAction::class)->execute($entry);

        expect(Logbook::find($entryId))->toBeNull();
    });

    it('creates activity log on delete', function () {
        $entry = Logbook::factory()->create();
        $entryId = $entry->id;

        app(DeleteLogbookAction::class)->execute($entry);

        $activity = Activity::where('event', 'logbook_entry_deleted')->first();
        expect($activity)->not->toBeNull();
        expect($activity->causer_id)->toBe($this->actor->id);
        expect($activity->subject_id)->toBe($entryId);
    });

    it('decrements logbook entry count', function () {
        Logbook::factory()->count(3)->create();

        $entry = Logbook::first();

        app(DeleteLogbookAction::class)->execute($entry);

        expect(Logbook::count())->toBe(2);
    });
});

use App\Livewire\Logbook\LogbookManager;
use Livewire\Livewire;

describe('authorization', function () {

    it('allows super_admin to access the page', function () {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $this->actingAs($user);

        Livewire::test(LogbookManager::class)
            ->assertSuccessful();
    });

    it('allows admin to access the page', function () {
        $user = User::factory()->create();
        $user->assignRole('admin');
        $this->actingAs($user);

        Livewire::test(LogbookManager::class)
            ->assertSuccessful();
    });

    it('allows teacher to access the page', function () {
        $user = User::factory()->create();
        $user->assignRole('teacher');
        $this->actingAs($user);

        Livewire::test(LogbookManager::class)
            ->assertSuccessful();
    });

    it('allows supervisor to access the page', function () {
        $user = User::factory()->create();
        $user->assignRole('supervisor');
        $this->actingAs($user);

        Livewire::test(LogbookManager::class)
            ->assertSuccessful();
    });

    it('blocks student from accessing the page', function () {
        $user = User::factory()->create();
        $user->assignRole('student');
        $this->actingAs($user);

        Livewire::test(LogbookManager::class)
            ->assertForbidden();
    });

});

describe('super_admin access', function () {

    beforeEach(function () {
        $this->superAdmin = User::factory()->create(['name' => 'Super Admin']);
        $this->superAdmin->assignRole('super_admin');
        $this->actingAs($this->superAdmin);
    });

    it('renders the page and displays entries', function () {
        $student = User::factory()->create(['name' => 'Student User']);
        $student->assignRole('student');
        $reg = Registration::factory()->create([
            'student_id' => $student->id,
            'status' => 'active',
        ]);

        Logbook::factory()->create([
            'user_id' => $student->id,
            'registration_id' => $reg->id,
            'content' => 'Test logbook entry',
        ]);

        Livewire::test(LogbookManager::class)
            ->assertSuccessful()
            ->assertSee('Student User');
    });

    it('filters entries by student name', function () {
        $student1 = User::factory()->create(['name' => 'Alice Student']);
        $student1->assignRole('student');
        $reg1 = Registration::factory()->create([
            'student_id' => $student1->id,
            'status' => 'active',
        ]);
        Logbook::factory()->create(['user_id' => $student1->id, 'registration_id' => $reg1->id]);

        $student2 = User::factory()->create(['name' => 'Bob Student']);
        $student2->assignRole('student');
        $reg2 = Registration::factory()->create([
            'student_id' => $student2->id,
            'status' => 'active',
        ]);
        Logbook::factory()->create(['user_id' => $student2->id, 'registration_id' => $reg2->id]);

        Livewire::test(LogbookManager::class)
            ->set('search', 'Alice')
            ->assertSee('Alice Student');
    });

    it('filters entries by content', function () {
        $student = User::factory()->create(['name' => 'Search Student']);
        $student->assignRole('student');
        $reg = Registration::factory()->create([
            'student_id' => $student->id,
            'status' => 'active',
        ]);
        Logbook::factory()->create([
            'user_id' => $student->id,
            'registration_id' => $reg->id,
            'content' => 'Unique content for searching',
        ]);

        Livewire::test(LogbookManager::class)
            ->set('search', 'Unique content')
            ->assertSee('Search Student');
    });

    it('opens the create modal', function () {
        Livewire::test(LogbookManager::class)
            ->call('create')
            ->assertSet('showModal', true)
            ->assertSet('formData.id', null);
    });

    it('creates a new entry for a student', function () {
        $student = User::factory()->create();
        $student->assignRole('student');
        $reg = Registration::factory()->create([
            'student_id' => $student->id,
            'status' => 'active',
        ]);

        Livewire::test(LogbookManager::class)
            ->call('create')
            ->set('formData.user_id', $student->id)
            ->set('formData.date', '2026-07-01')
            ->set('formData.content', 'Today I worked on the API integration module.')
            ->call('save')
            ->assertHasNoErrors();

        $entry = Logbook::where('user_id', $student->id)->first();
        expect($entry)->not->toBeNull();
        expect($entry->content)->toBe('Today I worked on the API integration module.');
    });

    it('validates required fields on create', function () {
        Livewire::test(LogbookManager::class)
            ->call('create')
            ->set('formData.content', '')
            ->set('formData.date', '')
            ->call('save')
            ->assertHasErrors([
                'formData.date' => 'required',
                'formData.content' => 'required',
            ]);
    });

    it('opens the edit modal with entry data', function () {
        $student = User::factory()->create();
        $student->assignRole('student');
        $reg = Registration::factory()->create([
            'student_id' => $student->id,
            'status' => 'active',
        ]);

        $entry = Logbook::factory()->create([
            'user_id' => $student->id,
            'registration_id' => $reg->id,
            'content' => 'Original content',
            'mentor_feedback' => 'Good job!',
        ]);

        Livewire::test(LogbookManager::class)
            ->call('edit', $entry->id)
            ->assertSet('showModal', true)
            ->assertSet('formData.id', $entry->id)
            ->assertSet('formData.content', 'Original content')
            ->assertSet('formData.mentor_feedback', 'Good job!');
    });

    it('updates an existing entry', function () {
        $student = User::factory()->create();
        $student->assignRole('student');
        $reg = Registration::factory()->create([
            'student_id' => $student->id,
            'status' => 'active',
        ]);

        $entry = Logbook::factory()->create([
            'user_id' => $student->id,
            'registration_id' => $reg->id,
            'content' => 'Before edit',
        ]);

        Livewire::test(LogbookManager::class)
            ->call('edit', $entry->id)
            ->set('formData.content', 'After edit')
            ->call('save')
            ->assertHasNoErrors();

        expect($entry->fresh()->content)->toBe('After edit');
    });

    it('deletes an entry', function () {
        $student = User::factory()->create();
        $student->assignRole('student');
        $reg = Registration::factory()->create([
            'student_id' => $student->id,
            'status' => 'active',
        ]);
        $entry = Logbook::factory()->create([
            'user_id' => $student->id,
            'registration_id' => $reg->id,
        ]);

        Livewire::test(LogbookManager::class)
            ->call('delete', $entry->id);

        expect(Logbook::find($entry->id))->toBeNull();
    });

    it('deletes selected entries in bulk', function () {
        $student = User::factory()->create();
        $student->assignRole('student');
        $reg = Registration::factory()->create([
            'student_id' => $student->id,
            'status' => 'active',
        ]);

        $entry1 = Logbook::factory()->create([
            'user_id' => $student->id,
            'registration_id' => $reg->id,
            'date' => '2026-07-01',
        ]);
        $entry2 = Logbook::factory()->create([
            'user_id' => $student->id,
            'registration_id' => $reg->id,
            'date' => '2026-07-02',
        ]);

        Livewire::test(LogbookManager::class)
            ->set('selectedIds', [$entry1->id, $entry2->id])
            ->call('deleteSelected');

        expect(Logbook::find($entry1->id))->toBeNull();
        expect(Logbook::find($entry2->id))->toBeNull();
    });

    it('toggles verification on an entry', function () {
        $student = User::factory()->create();
        $student->assignRole('student');
        $reg = Registration::factory()->create([
            'student_id' => $student->id,
            'status' => 'active',
        ]);

        $entry = Logbook::factory()->create([
            'user_id' => $student->id,
            'registration_id' => $reg->id,
            'is_verified' => false,
        ]);

        Livewire::test(LogbookManager::class)
            ->call('verify', $entry->id);

        expect($entry->fresh()->is_verified)->toBeTrue();
    });

});

describe('teacher scoping', function () {

    beforeEach(function () {
        $this->teacher = User::factory()->create(['name' => 'Mr. Teacher']);
        $this->teacher->assignRole('teacher');
        $this->actingAs($this->teacher);
    });

    it('sees only entries from assigned students', function () {
        $assignedStudent = User::factory()->create(['name' => 'Assigned Student']);
        $assignedStudent->assignRole('student');
        $reg1 = Registration::factory()->create([
            'student_id' => $assignedStudent->id,
            'teacher_id' => $this->teacher->id,
            'status' => 'active',
        ]);
        Logbook::factory()->create([
            'user_id' => $assignedStudent->id,
            'registration_id' => $reg1->id,
        ]);

        $otherStudent = User::factory()->create(['name' => 'Other Student']);
        $otherStudent->assignRole('student');
        $reg2 = Registration::factory()->create([
            'student_id' => $otherStudent->id,
            'status' => 'active',
        ]);
        Logbook::factory()->create([
            'user_id' => $otherStudent->id,
            'registration_id' => $reg2->id,
        ]);

        Livewire::test(LogbookManager::class)
            ->assertSee('Assigned Student')
            ->assertDontSee('Other Student');
    });

    it('can create entry for assigned student', function () {
        $student = User::factory()->create();
        $student->assignRole('student');
        $reg = Registration::factory()->create([
            'student_id' => $student->id,
            'teacher_id' => $this->teacher->id,
            'status' => 'active',
        ]);

        Livewire::test(LogbookManager::class)
            ->call('create')
            ->set('formData.user_id', $student->id)
            ->set('formData.date', '2026-07-01')
            ->set('formData.content', 'Teacher created this entry.')
            ->call('save')
            ->assertHasNoErrors();

        expect(Logbook::where('user_id', $student->id)->exists())->toBeTrue();
    });

});

describe('supervisor scoping', function () {

    beforeEach(function () {
        $this->supervisor = User::factory()->create(['name' => 'Mrs. Supervisor']);
        $this->supervisor->assignRole('supervisor');
        $this->actingAs($this->supervisor);
    });

    it('sees only entries from assigned students', function () {
        $assignedStudent = User::factory()->create(['name' => 'Mentee Student']);
        $assignedStudent->assignRole('student');
        $reg1 = Registration::factory()->create([
            'student_id' => $assignedStudent->id,
            'mentor_id' => $this->supervisor->id,
            'status' => 'active',
        ]);
        Logbook::factory()->create([
            'user_id' => $assignedStudent->id,
            'registration_id' => $reg1->id,
        ]);

        $otherStudent = User::factory()->create(['name' => 'Unrelated Student']);
        $otherStudent->assignRole('student');
        $reg2 = Registration::factory()->create([
            'student_id' => $otherStudent->id,
            'status' => 'active',
        ]);
        Logbook::factory()->create([
            'user_id' => $otherStudent->id,
            'registration_id' => $reg2->id,
        ]);

        Livewire::test(LogbookManager::class)
            ->assertSee('Mentee Student')
            ->assertDontSee('Unrelated Student');
    });

});

describe('admin access', function () {

    beforeEach(function () {
        $this->admin = User::factory()->create(['name' => 'Regular Admin']);
        $this->admin->assignRole('admin');
        $this->actingAs($this->admin);
    });

    it('renders the page and displays entries', function () {
        $student = User::factory()->create(['name' => 'Admin View Student']);
        $student->assignRole('student');
        $reg = Registration::factory()->create([
            'student_id' => $student->id,
            'status' => 'active',
        ]);
        Logbook::factory()->create([
            'user_id' => $student->id,
            'registration_id' => $reg->id,
        ]);

        Livewire::test(LogbookManager::class)
            ->assertSuccessful()
            ->assertSee('Admin View Student');
    });

    it('can create a new entry', function () {
        $student = User::factory()->create();
        $student->assignRole('student');
        $reg = Registration::factory()->create([
            'student_id' => $student->id,
            'status' => 'active',
        ]);

        Livewire::test(LogbookManager::class)
            ->call('create')
            ->set('formData.user_id', $student->id)
            ->set('formData.date', '2026-07-01')
            ->set('formData.content', 'Admin created this entry for the student.')
            ->call('save')
            ->assertHasNoErrors();

        expect(Logbook::where('user_id', $student->id)->exists())->toBeTrue();
    });

    it('can toggle verification', function () {
        $student = User::factory()->create();
        $student->assignRole('student');
        $reg = Registration::factory()->create([
            'student_id' => $student->id,
            'status' => 'active',
        ]);
        $entry = Logbook::factory()->create([
            'user_id' => $student->id,
            'registration_id' => $reg->id,
            'is_verified' => false,
        ]);

        Livewire::test(LogbookManager::class)
            ->call('verify', $entry->id);

        expect($entry->fresh()->is_verified)->toBeTrue();
    });

});
