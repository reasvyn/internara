<?php

declare(strict_types=1);

use App\Domain\Admin\Livewire\UserManager;
use App\Domain\Auth\Enums\Role;
use App\Domain\User\Models\User;
use Spatie\Permission\Models\Role as RoleModel;

beforeEach(function () {
    app()->setLocale('en');
    collect(Role::cases())->each(fn ($r) => RoleModel::create(['name' => $r->value, 'guard_name' => 'web']));
    $this->admin = User::factory()->create()->assignRole(Role::SUPER_ADMIN->value);
    $this->actingAs($this->admin);
});

describe('UserManager', function () {
    it('renders the page', function () {
        Livewire::test(UserManager::class)
            ->assertSuccessful();
    });

    it('blocks unauthorized users', function () {
        $student = User::factory()->create();
        $this->actingAs($student);

        Livewire::test(UserManager::class)
            ->assertForbidden();
    });

    it('creates a user', function () {
        Livewire::test(UserManager::class)
            ->call('createUser')
            ->set('form.name', 'New User')
            ->set('form.email', 'new@example.com')
            ->set('form.password', 'secret123')
            ->set('form.roles', [Role::TEACHER->value])
            ->call('saveUser')
            ->assertHasNoErrors();

        expect(User::where('email', 'new@example.com')->exists())->toBeTrue();
    });

    it('validates required fields on create', function () {
        Livewire::test(UserManager::class)
            ->call('createUser')
            ->set('form.name', '')
            ->set('form.email', '')
            ->set('form.password', '')
            ->set('form.roles', [])
            ->call('saveUser')
            ->assertHasErrors(['form.name', 'form.email', 'form.password', 'form.roles']);
    });

    it('edits a user', function () {
        $user = User::factory()->create()->assignRole(Role::STUDENT->value);

        Livewire::test(UserManager::class)
            ->call('editUser', $user->id)
            ->set('form.name', 'Updated Name')
            ->set('form.email', 'updated@example.com')
            ->call('saveUser')
            ->assertHasNoErrors();

        expect($user->fresh()->name)->toBe('Updated Name');
    });

    it('deletes a user', function () {
        $user = User::factory()->create();

        Livewire::test(UserManager::class)
            ->call('deleteUser', $user->id)
            ->assertHasNoErrors();

        expect(User::find($user->id))->toBeNull();
    });

    it('searches users', function () {
        User::factory()->create(['name' => 'Alice']);
        User::factory()->create(['name' => 'Bob']);

        Livewire::test(UserManager::class)
            ->set('search', 'Alice')
            ->assertSuccessful();
    });

    it('toggles user status', function () {
        $user = User::factory()->create();
        $user->setStatus('verified');

        Livewire::test(UserManager::class)
            ->call('toggleStatus', $user->id)
            ->assertHasNoErrors();
    });
});
