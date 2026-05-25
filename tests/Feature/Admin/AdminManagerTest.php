<?php

declare(strict_types=1);

use App\Domain\Admin\Livewire\AdminManager;
use App\Domain\Auth\Enums\Role;
use App\Domain\User\Models\User;
use Spatie\Permission\Models\Role as RoleModel;

beforeEach(function () {
    app()->setLocale('en');
    collect(Role::cases())->each(fn ($r) => RoleModel::create(['name' => $r->value, 'guard_name' => 'web']));
    $this->admin = User::factory()->create()->assignRole(Role::SUPER_ADMIN->value);
    $this->actingAs($this->admin);
});

describe('AdminManager', function () {
    it('renders the page', function () {
        Livewire::test(AdminManager::class)
            ->assertSuccessful();
    });

    it('blocks unauthorized users', function () {
        $student = User::factory()->create();
        $this->actingAs($student);

        Livewire::test(AdminManager::class)
            ->assertForbidden();
    });

    it('creates an admin user', function () {
        Livewire::test(AdminManager::class)
            ->call('create')
            ->set('form.name', 'Admin Baru')
            ->set('form.email', 'admin@example.com')
            ->call('save')
            ->assertHasNoErrors();

        expect(User::where('email', 'admin@example.com')->exists())->toBeTrue();
    });

    it('validates required name', function () {
        Livewire::test(AdminManager::class)
            ->call('create')
            ->set('form.name', '')
            ->set('form.email', '')
            ->call('save')
            ->assertHasErrors(['form.name', 'form.email']);
    });

    it('edits an admin user', function () {
        $admin = User::factory()->create()->assignRole(Role::ADMIN->value);

        Livewire::test(AdminManager::class)
            ->call('edit', $admin->id)
            ->set('form.name', 'Updated Admin')
            ->set('form.email', $admin->email)
            ->call('save')
            ->assertHasNoErrors();

        expect($admin->fresh()->name)->toBe('Updated Admin');
    });

    it('cannot delete self', function () {
        Livewire::test(AdminManager::class)
            ->call('delete', $this->admin->id)
            ->assertHasNoErrors();

        expect(User::find($this->admin->id))->not->toBeNull();
    });

    it('deletes another admin', function () {
        $target = User::factory()->create()->assignRole(Role::ADMIN->value);

        Livewire::test(AdminManager::class)
            ->call('delete', $target->id)
            ->assertHasNoErrors();

        expect(User::find($target->id))->toBeNull();
    });
});
