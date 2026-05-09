<?php

declare(strict_types=1);

use App\Livewire\User\Admin\AdminManager;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

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
        Livewire::test(AdminManager::class)
            ->assertSuccessful();
    });

    it('blocks admin from accessing', function () {
        $user = User::factory()->create()->assignRole('admin');
        $this->actingAs($user);

        Livewire::test(AdminManager::class)
            ->assertForbidden();
    });

});

describe('rendering', function () {

    it('renders the admin manager page', function () {
        Livewire::test(AdminManager::class)
            ->assertSuccessful()
            ->assertSet('search', '');
    });

    it('displays admin users in the table', function () {
        $admin1 = User::factory()->create(['name' => 'Alice Admin'])->assignRole('admin');
        $admin2 = User::factory()->create(['name' => 'Bob Admin'])->assignRole('admin');

        Livewire::test(AdminManager::class)
            ->assertSee('Alice Admin')
            ->assertSee('Bob Admin');
    });

    it('does not display non-admin users', function () {
        User::factory()->create(['name' => 'Regular User'])->assignRole('student');

        Livewire::test(AdminManager::class)
            ->assertDontSee('Regular User');
    });

});

describe('search', function () {

    it('filters admins by name', function () {
        User::factory()->create(['name' => 'Unique Admin'])->assignRole('admin');
        User::factory()->create(['name' => 'Other Admin'])->assignRole('admin');

        Livewire::test(AdminManager::class)
            ->set('search', 'Unique')
            ->assertSee('Unique Admin')
            ->assertDontSee('Other Admin');
    });

    it('filters admins by email', function () {
        User::factory()->create(['email' => 'unique@example.com'])->assignRole('admin');
        User::factory()->create(['email' => 'other@example.com'])->assignRole('admin');

        Livewire::test(AdminManager::class)
            ->set('search', 'unique@')
            ->assertSee('unique@example.com')
            ->assertDontSee('other@example.com');
    });

});

describe('create', function () {

    it('opens the create modal', function () {
        Livewire::test(AdminManager::class)
            ->call('create')
            ->assertSet('userModal', true)
            ->assertSet('userData.id', null);
    });

    it('creates a new admin user', function () {
        Livewire::test(AdminManager::class)
            ->call('create')
            ->set('userData.name', 'New Admin')
            ->set('userData.email', 'newadmin@example.com')
            ->call('save')
            ->assertHasNoErrors();

        $user = User::where('email', 'newadmin@example.com')->first();
        expect($user)->not->toBeNull();
        expect($user->name)->toBe('New Admin');
        expect($user->hasRole('admin'))->toBeTrue();
    });

    it('validates required fields on create', function () {
        Livewire::test(AdminManager::class)
            ->call('create')
            ->call('save')
            ->assertHasErrors([
                'userData.name' => 'required',
                'userData.email' => 'required',
            ]);
    });

    it('validates email uniqueness on create', function () {
        User::factory()->create(['email' => 'existing@example.com']);

        Livewire::test(AdminManager::class)
            ->call('create')
            ->set('userData.name', 'Test')
            ->set('userData.email', 'existing@example.com')
            ->call('save')
            ->assertHasErrors(['userData.email' => 'unique']);
    });

});

describe('edit', function () {

    it('opens the edit modal with user data', function () {
        $user = User::factory()->create(['name' => 'Edit Admin'])->assignRole('admin');

        Livewire::test(AdminManager::class)
            ->call('edit', $user->id)
            ->assertSet('userModal', true)
            ->assertSet('userData.id', $user->id)
            ->assertSet('userData.name', 'Edit Admin');
    });

    it('updates admin data', function () {
        $user = User::factory()->create([
            'name' => 'Before Edit',
            'email' => 'before@example.com',
        ])->assignRole('admin');

        Livewire::test(AdminManager::class)
            ->call('edit', $user->id)
            ->set('userData.name', 'After Edit')
            ->set('userData.email', 'after@example.com')
            ->call('save')
            ->assertHasNoErrors();

        expect($user->fresh()->name)->toBe('After Edit');
        expect($user->fresh()->email)->toBe('after@example.com');
    });

});

describe('delete', function () {

    it('deletes an admin user', function () {
        $user = User::factory()->create()->assignRole('admin');

        Livewire::test(AdminManager::class)
            ->call('delete', $user->id);

        expect(User::find($user->id))->toBeNull();
    });

    it('prevents self-deletion', function () {
        Livewire::test(AdminManager::class)
            ->call('delete', $this->admin->id);

        expect(User::find($this->admin->id))->not->toBeNull();
    });

});

describe('bulk delete', function () {

    it('deletes selected admins', function () {
        $admin1 = User::factory()->create()->assignRole('admin');
        $admin2 = User::factory()->create()->assignRole('admin');

        Livewire::test(AdminManager::class)
            ->set('selectedIds', [$admin1->id, $admin2->id])
            ->call('deleteSelected');

        expect(User::find($admin1->id))->toBeNull();
        expect(User::find($admin2->id))->toBeNull();
    });

    it('skips self in bulk delete', function () {
        $other = User::factory()->create()->assignRole('admin');

        Livewire::test(AdminManager::class)
            ->set('selectedIds', [$this->admin->id, $other->id])
            ->call('deleteSelected');

        expect(User::find($this->admin->id))->not->toBeNull();
        expect(User::find($other->id))->toBeNull();
    });

});
