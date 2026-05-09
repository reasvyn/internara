<?php

declare(strict_types=1);

use App\Livewire\User\Admin\MenteeManager;
use App\Models\Mentee;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

beforeEach(function () {
    Role::create(['name' => 'super_admin', 'guard_name' => 'web']);
    Role::create(['name' => 'admin', 'guard_name' => 'web']);
    Role::create(['name' => 'teacher', 'guard_name' => 'web']);
    Role::create(['name' => 'student', 'guard_name' => 'web']);
    Role::create(['name' => 'supervisor', 'guard_name' => 'web']);

    $this->admin = User::factory()->create(['name' => 'Super Admin']);
    $this->admin->assignRole('super_admin');

    $this->actingAs($this->admin);
});

describe('access control', function () {

    it('allows super_admin to access', function () {
        Livewire::test(MenteeManager::class)
            ->assertSuccessful();
    });

    it('allows admin to access', function () {
        $user = User::factory()->create()->assignRole('admin');
        $this->actingAs($user);

        Livewire::test(MenteeManager::class)
            ->assertSuccessful();
    });

    it('blocks teacher from accessing', function () {
        $user = User::factory()->create()->assignRole('teacher');
        $this->actingAs($user);

        Livewire::test(MenteeManager::class)
            ->assertForbidden();
    });

    it('blocks student from accessing', function () {
        $user = User::factory()->create()->assignRole('student');
        $this->actingAs($user);

        Livewire::test(MenteeManager::class)
            ->assertForbidden();
    });

});

describe('rendering', function () {

    it('renders the mentee manager page', function () {
        Livewire::test(MenteeManager::class)
            ->assertSuccessful()
            ->assertSet('search', '');
    });

    it('displays mentees in the table', function () {
        Mentee::factory()->create([
            'user_id' => User::factory()->create(['name' => 'Alice Mentee'])->assignRole('student')->id,
        ]);
        Mentee::factory()->create([
            'user_id' => User::factory()->create(['name' => 'Bob Mentee'])->assignRole('student')->id,
        ]);

        Livewire::test(MenteeManager::class)
            ->assertSee('Alice Mentee')
            ->assertSee('Bob Mentee');
    });

});

describe('search', function () {

    it('filters mentees by name', function () {
        Mentee::factory()->create([
            'user_id' => User::factory()->create(['name' => 'Unique Mentee'])->id,
        ]);
        Mentee::factory()->create([
            'user_id' => User::factory()->create(['name' => 'Other Mentee'])->id,
        ]);

        Livewire::test(MenteeManager::class)
            ->set('search', 'Unique')
            ->assertSee('Unique Mentee')
            ->assertDontSee('Other Mentee');
    });

    it('filters mentees by email', function () {
        Mentee::factory()->create([
            'user_id' => User::factory()->create(['email' => 'unique@example.com'])->id,
        ]);
        Mentee::factory()->create([
            'user_id' => User::factory()->create(['email' => 'other@example.com'])->id,
        ]);

        Livewire::test(MenteeManager::class)
            ->set('search', 'unique@')
            ->assertSee('unique@example.com')
            ->assertDontSee('other@example.com');
    });

});

describe('create', function () {

    it('opens the create modal', function () {
        Livewire::test(MenteeManager::class)
            ->call('create')
            ->assertSet('userModal', true)
            ->assertSet('userData.id', null);
    });

    it('creates a new mentee with student role', function () {
        Livewire::test(MenteeManager::class)
            ->call('create')
            ->set('userData.name', 'New Mentee')
            ->set('userData.email', 'newmentee@example.com')
            ->set('userData.internal_notes', 'Test notes')
            ->call('save')
            ->assertHasNoErrors();

        $mentee = Mentee::whereHas('user', fn ($q) => $q->where('email', 'newmentee@example.com'))->first();

        expect($mentee)->not->toBeNull();
        expect($mentee->is_active)->toBeTrue();
        expect($mentee->internal_notes)->toBe('Test notes');
        expect($mentee->user->name)->toBe('New Mentee');
        expect($mentee->user->hasRole('student'))->toBeTrue();

        assertDatabaseHas('mentees', ['id' => $mentee->id]);
    });

    it('validates required fields on create', function () {
        Livewire::test(MenteeManager::class)
            ->call('create')
            ->call('save')
            ->assertHasErrors([
                'userData.name' => 'required',
                'userData.email' => 'required',
            ]);
    });

    it('validates email uniqueness on create', function () {
        User::factory()->create(['email' => 'existing@example.com']);

        Livewire::test(MenteeManager::class)
            ->call('create')
            ->set('userData.name', 'Test')
            ->set('userData.email', 'existing@example.com')
            ->call('save')
            ->assertHasErrors(['userData.email' => 'unique']);
    });

});

describe('edit', function () {

    it('opens the edit modal with mentee data', function () {
        $mentee = Mentee::factory()->create();
        $mentee->user->assignRole('student');

        Livewire::test(MenteeManager::class)
            ->call('edit', $mentee->id)
            ->assertSet('userModal', true)
            ->assertSet('userData.id', $mentee->id)
            ->assertSet('userData.name', $mentee->user->name);
    });

    it('updates mentee data', function () {
        $mentee = Mentee::factory()->create();
        $mentee->user->assignRole('student');

        Livewire::test(MenteeManager::class)
            ->call('edit', $mentee->id)
            ->set('userData.internal_notes', 'Updated notes')
            ->set('userData.is_active', false)
            ->call('save')
            ->assertHasNoErrors();

        $fresh = $mentee->fresh();

        expect($fresh->internal_notes)->toBe('Updated notes');
        expect($fresh->is_active)->toBeFalse();
    });

});

describe('delete', function () {

    it('deletes a mentee and cascades to user', function () {
        $mentee = Mentee::factory()->create();
        $mentee->user->assignRole('student');
        $userId = $mentee->user_id;

        Livewire::test(MenteeManager::class)
            ->call('delete', $mentee->id);

        expect(Mentee::find($mentee->id))->toBeNull();
        expect(User::find($userId))->toBeNull();
        assertDatabaseMissing('mentees', ['id' => $mentee->id]);
    });

});

describe('bulk delete', function () {

    it('deletes selected mentees', function () {
        $mentee1 = Mentee::factory()->create();
        $mentee1->user->assignRole('student');
        $mentee2 = Mentee::factory()->create();
        $mentee2->user->assignRole('student');

        Livewire::test(MenteeManager::class)
            ->set('selectedIds', [$mentee1->id, $mentee2->id])
            ->call('deleteSelected');

        expect(Mentee::find($mentee1->id))->toBeNull();
        expect(Mentee::find($mentee2->id))->toBeNull();
        assertDatabaseMissing('mentees', ['id' => $mentee1->id]);
        assertDatabaseMissing('mentees', ['id' => $mentee2->id]);
    });

});
