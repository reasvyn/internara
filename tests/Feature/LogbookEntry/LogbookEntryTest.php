<?php

declare(strict_types=1);

use App\Livewire\Logbook\LogbookEntry;
use App\Models\Logbook;
use App\Models\Registration;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::create(['name' => 'super_admin', 'guard_name' => 'web']);
    Role::create(['name' => 'admin', 'guard_name' => 'web']);
    Role::create(['name' => 'teacher', 'guard_name' => 'web']);
    Role::create(['name' => 'supervisor', 'guard_name' => 'web']);
    Role::create(['name' => 'student', 'guard_name' => 'web']);
});

describe('authorization', function () {

    it('allows student to access the page', function () {
        $user = User::factory()->create();
        $user->assignRole('student');
        $this->actingAs($user);

        Livewire::test(LogbookEntry::class)
            ->assertSuccessful();
    });

    it('blocks super_admin from accessing the page', function () {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $this->actingAs($user);

        Livewire::test(LogbookEntry::class)
            ->assertForbidden();
    });

    it('blocks admin from accessing the page', function () {
        $user = User::factory()->create();
        $user->assignRole('admin');
        $this->actingAs($user);

        Livewire::test(LogbookEntry::class)
            ->assertForbidden();
    });

    it('blocks teacher from accessing the page', function () {
        $user = User::factory()->create();
        $user->assignRole('teacher');
        $this->actingAs($user);

        Livewire::test(LogbookEntry::class)
            ->assertForbidden();
    });

    it('blocks supervisor from accessing the page', function () {
        $user = User::factory()->create();
        $user->assignRole('supervisor');
        $this->actingAs($user);

        Livewire::test(LogbookEntry::class)
            ->assertForbidden();
    });

});

describe('student operations', function () {

    beforeEach(function () {
        $this->student = User::factory()->create(['name' => 'Test Student']);
        $this->student->assignRole('student');
        $this->actingAs($this->student);
    });

    it('renders the page and shows own entries', function () {
        $reg = Registration::factory()->create([
            'student_id' => $this->student->id,
            'status' => 'active',
        ]);

        Logbook::factory()->create([
            'user_id' => $this->student->id,
            'registration_id' => $reg->id,
            'content' => 'My daily activity',
        ]);

        $otherStudent = User::factory()->create();
        $otherReg = Registration::factory()->create(['student_id' => $otherStudent->id]);
        Logbook::factory()->create([
            'user_id' => $otherStudent->id,
            'registration_id' => $otherReg->id,
            'content' => 'Not mine',
        ]);

        Livewire::test(LogbookEntry::class)
            ->assertSuccessful()
            ->assertSee('My daily activity')
            ->assertDontSee('Not mine');
    });

    it('opens the create modal', function () {
        Livewire::test(LogbookEntry::class)
            ->call('create')
            ->assertSet('showModal', true)
            ->assertSet('journalId', '');
    });

    it('validates required fields', function () {
        Livewire::test(LogbookEntry::class)
            ->call('create')
            ->set('content', '')
            ->set('date', '')
            ->call('save')
            ->assertHasErrors([
                'date' => 'required',
                'content' => 'required',
            ]);
    });

    it('validates content minimum length', function () {
        Livewire::test(LogbookEntry::class)
            ->call('create')
            ->set('date', now()->toDateString())
            ->set('content', 'Short')
            ->call('save')
            ->assertHasErrors(['content' => 'min']);
    });

    it('creates a new journal entry when registration exists', function () {
        $reg = Registration::factory()->create([
            'student_id' => $this->student->id,
        ]);
        $reg->setStatus('active');

        Livewire::test(LogbookEntry::class)
            ->call('create')
            ->set('content', 'Today I learned about API development and database design.')
            ->call('save')
            ->assertHasNoErrors();

        $entry = Logbook::where('user_id', $this->student->id)->first();
        expect($entry)->not->toBeNull();
        expect($entry->content)->toBe('Today I learned about API development and database design.');
        expect($entry->status->value)->toBe('submitted');
    });

    it('shows error when no active registration', function () {
        Livewire::test(LogbookEntry::class)
            ->call('create')
            ->set('content', 'This is my daily activity content that is long enough.')
            ->call('save');

        $entry = Logbook::where('user_id', $this->student->id)->first();
        expect($entry)->toBeNull();
    });

    it('opens the edit modal with entry data', function () {
        $reg = Registration::factory()->create([
            'student_id' => $this->student->id,
            'status' => 'active',
        ]);

        $entry = Logbook::factory()->create([
            'user_id' => $this->student->id,
            'registration_id' => $reg->id,
            'date' => '2026-07-15',
            'content' => 'Original content for editing',
            'learning_outcomes' => 'Learned about testing',
        ]);

        Livewire::test(LogbookEntry::class)
            ->call('edit', $entry->id)
            ->assertSet('showModal', true)
            ->assertSet('journalId', $entry->id)
            ->assertSet('date', '2026-07-15')
            ->assertSet('content', 'Original content for editing')
            ->assertSet('learning_outcomes', 'Learned about testing');
    });

});
