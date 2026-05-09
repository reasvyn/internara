<?php

declare(strict_types=1);

use App\Enums\Auth\Role as RoleEnum;
use App\Livewire\User\Admin\SupervisorManager;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
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
        Livewire::test(SupervisorManager::class)
            ->assertSuccessful();
    });

    it('allows admin to access', function () {
        $user = User::factory()->create()->assignRole('admin');
        $this->actingAs($user);

        Livewire::test(SupervisorManager::class)
            ->assertSuccessful();
    });

    it('blocks teacher from accessing', function () {
        $user = User::factory()->create()->assignRole('teacher');
        $this->actingAs($user);

        Livewire::test(SupervisorManager::class)
            ->assertForbidden();
    });

    it('blocks student from accessing', function () {
        $user = User::factory()->create()->assignRole('student');
        $this->actingAs($user);

        Livewire::test(SupervisorManager::class)
            ->assertForbidden();
    });

});

describe('rendering', function () {

    it('renders the supervisor manager page', function () {
        Livewire::test(SupervisorManager::class)
            ->assertSuccessful()
            ->assertSet('search', '');
    });

    it('displays supervisors in the table', function () {
        User::factory()->create(['name' => 'Alice Supervisor'])->assignRole('supervisor');
        User::factory()->create(['name' => 'Bob Supervisor'])->assignRole('supervisor');

        Livewire::test(SupervisorManager::class)
            ->assertSee('Alice Supervisor')
            ->assertSee('Bob Supervisor');
    });

    it('does not display non-supervisor users', function () {
        User::factory()->create(['name' => 'Some Teacher'])->assignRole('teacher');

        Livewire::test(SupervisorManager::class)
            ->assertDontSee('Some Teacher');
    });

});

describe('search', function () {

    it('filters supervisors by name', function () {
        User::factory()->create(['name' => 'Unique Supervisor'])->assignRole('supervisor');
        User::factory()->create(['name' => 'Other Supervisor'])->assignRole('supervisor');

        Livewire::test(SupervisorManager::class)
            ->set('search', 'Unique')
            ->assertSee('Unique Supervisor')
            ->assertDontSee('Other Supervisor');
    });

    it('filters supervisors by email', function () {
        User::factory()->create(['email' => 'unique@example.com'])->assignRole('supervisor');
        User::factory()->create(['email' => 'other@example.com'])->assignRole('supervisor');

        Livewire::test(SupervisorManager::class)
            ->set('search', 'unique@')
            ->assertSee('unique@example.com')
            ->assertDontSee('other@example.com');
    });

});

describe('create', function () {

    it('opens the create modal', function () {
        Livewire::test(SupervisorManager::class)
            ->call('create')
            ->assertSet('userModal', true)
            ->assertSet('userData.id', null);
    });

    it('creates a new supervisor with role', function () {
        Livewire::test(SupervisorManager::class)
            ->call('create')
            ->set('userData.name', 'New Supervisor')
            ->set('userData.email', 'newsupervisor@example.com')
            ->call('save')
            ->assertHasNoErrors();

        $user = User::where('email', 'newsupervisor@example.com')->first();

        expect($user)->not->toBeNull();
        expect($user->name)->toBe('New Supervisor');
        expect($user->email)->toBe('newsupervisor@example.com');
        expect($user->username)->toStartWith('u');
        expect(strlen($user->username))->toBe(9);
        expect($user->hasRole(RoleEnum::SUPERVISOR->value))->toBeTrue();
        expect(Hash::check('newsupervisor@example.com', $user->password))->toBeFalse();

        assertDatabaseHas('users', ['email' => 'newsupervisor@example.com']);
    });

    it('validates required fields on create', function () {
        Livewire::test(SupervisorManager::class)
            ->call('create')
            ->call('save')
            ->assertHasErrors([
                'userData.name' => 'required',
                'userData.email' => 'required',
            ]);
    });

    it('validates email uniqueness on create', function () {
        User::factory()->create(['email' => 'existing@example.com']);

        Livewire::test(SupervisorManager::class)
            ->call('create')
            ->set('userData.name', 'Test')
            ->set('userData.email', 'existing@example.com')
            ->call('save')
            ->assertHasErrors(['userData.email' => 'unique']);
    });

});

describe('edit', function () {

    it('opens the edit modal with user data', function () {
        $user = User::factory()->create(['name' => 'Edit Supervisor'])->assignRole('supervisor');

        Livewire::test(SupervisorManager::class)
            ->call('edit', $user->id)
            ->assertSet('userModal', true)
            ->assertSet('userData.id', $user->id)
            ->assertSet('userData.name', 'Edit Supervisor');
    });

    it('updates supervisor data', function () {
        $user = User::factory()->create([
            'name' => 'Before Edit',
            'email' => 'before@example.com',
        ])->assignRole('supervisor');

        Livewire::test(SupervisorManager::class)
            ->call('edit', $user->id)
            ->set('userData.name', 'After Edit')
            ->set('userData.email', 'after@example.com')
            ->call('save')
            ->assertHasNoErrors();

        $fresh = $user->fresh();

        expect($fresh->name)->toBe('After Edit');
        expect($fresh->email)->toBe('after@example.com');
        expect($fresh->hasRole('supervisor'))->toBeTrue();

        assertDatabaseHas('users', ['id' => $user->id, 'name' => 'After Edit']);
    });

});

describe('delete', function () {

    it('deletes a supervisor', function () {
        $user = User::factory()->create()->assignRole('supervisor');

        Livewire::test(SupervisorManager::class)
            ->call('delete', $user->id);

        expect(User::find($user->id))->toBeNull();
        assertDatabaseMissing('users', ['id' => $user->id]);
    });

});

describe('bulk delete', function () {

    it('deletes selected supervisors', function () {
        $supervisor1 = User::factory()->create()->assignRole('supervisor');
        $supervisor2 = User::factory()->create()->assignRole('supervisor');

        Livewire::test(SupervisorManager::class)
            ->set('selectedIds', [$supervisor1->id, $supervisor2->id])
            ->call('deleteSelected');

        expect(User::find($supervisor1->id))->toBeNull();
        expect(User::find($supervisor2->id))->toBeNull();
        assertDatabaseMissing('users', ['id' => $supervisor1->id]);
        assertDatabaseMissing('users', ['id' => $supervisor2->id]);
    });

});
