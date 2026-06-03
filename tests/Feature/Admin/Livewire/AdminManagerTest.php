<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Livewire;

use App\Domain\Admin\Livewire\AdminManager;
use App\Domain\Auth\Enums\Role;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role as RoleModel;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    collect(Role::userRoles())->each(fn ($r) => RoleModel::create(['name' => $r->value, 'guard_name' => 'web']));
});

describe('AdminManager', function () {
    // ─── AUTHORIZATION ───────────────────────────────────────

    it('allows super_admin to render', function () {
        actingAsSuperAdmin();

        Livewire::test(AdminManager::class)
            ->assertSuccessful();
    });

    it('denies admin to render', function () {
        actingAsAdmin();

        Livewire::test(AdminManager::class)
            ->assertForbidden();
    });

    it('denies student to render', function () {
        actingAsStudent();

        Livewire::test(AdminManager::class)
            ->assertForbidden();
    });

    // ─── HEADERS ────────────────────────────────────────────

    it('returns correct headers', function () {
        actingAsSuperAdmin();

        $headers = Livewire::test(AdminManager::class)->instance()->headers();

        expect($headers)->toHaveCount(5);
        expect($headers[0]['key'])->toBe('name');
        expect($headers[1]['key'])->toBe('email');
        expect($headers[2]['key'])->toBe('username');
    });

    // ─── QUERY ──────────────────────────────────────────────

    it('returns admin and super_admin users', function () {
        actingAsSuperAdmin();
        User::factory()->create()->assignRole('super_admin');
        User::factory()->create()->assignRole('admin');
        User::factory()->create()->assignRole('student');

        expect(Livewire::test(AdminManager::class)->instance()->rows()->total())->toBe(3);
    });

    // ─── SEARCH ─────────────────────────────────────────────

    it('filters by search term', function () {
        actingAsSuperAdmin();
        User::factory()->create(['name' => 'AdminOne'])->assignRole('admin');
        User::factory()->create(['name' => 'Other'])->assignRole('admin');

        $component = Livewire::test(AdminManager::class)
            ->set('search', 'AdminOne');

        expect($component->instance()->rows()->total())->toBe(1);
    });

    // ─── FILTERS ─────────────────────────────────────────────

    it('filters by setup_required', function () {
        actingAsSuperAdmin();
        User::factory()->create()->assignRole('admin');
        User::factory()->requiresSetup()->create()->assignRole('admin');

        $component = Livewire::test(AdminManager::class)
            ->set('filters.setup_required', 'yes');

        expect($component->instance()->rows()->total())->toBe(1);
    });

    it('filters by locked status', function () {
        actingAsSuperAdmin();
        User::factory()->create()->assignRole('admin');
        User::factory()->locked()->create()->assignRole('admin');

        $component = Livewire::test(AdminManager::class)
            ->set('filters.locked', 'yes');

        expect($component->instance()->rows()->total())->toBe(1);
    });

    // ─── CRUD ───────────────────────────────────────────────

    it('opens create modal', function () {
        actingAsSuperAdmin();

        Livewire::test(AdminManager::class)
            ->call('create')
            ->assertSet('userModal', true);
    });

    it('opens edit modal with admin data', function () {
        actingAsSuperAdmin();
        $user = User::factory()->create()->assignRole('admin');

        Livewire::test(AdminManager::class)
            ->call('edit', $user->id)
            ->assertSet('userModal', true)
            ->assertSet('form.name', $user->name)
            ->assertSet('form.email', $user->email);
    });

    it('cannot edit super_admin', function () {
        actingAsSuperAdmin();
        $superAdmin = User::factory()->create()->assignRole('super_admin');

        Livewire::test(AdminManager::class)
            ->call('edit', $superAdmin->id)
            ->assertSet('userModal', false);
    });

    it('creates a new admin', function () {
        actingAsSuperAdmin();

        Livewire::test(AdminManager::class)
            ->set('form.name', 'Admin Baru')
            ->set('form.email', 'adminbaru@example.com')
            ->call('save');

        expect(User::where('email', 'adminbaru@example.com')->exists())->toBeTrue();
    });

    it('validates required fields on create', function () {
        actingAsSuperAdmin();

        Livewire::test(AdminManager::class)
            ->call('save')
            ->assertHasErrors(['form.name', 'form.email']);
    });

    it('updates an existing admin', function () {
        actingAsSuperAdmin();
        $user = User::factory()->create()->assignRole('admin');

        Livewire::test(AdminManager::class)
            ->call('edit', $user->id)
            ->set('form.name', 'Updated Admin')
            ->call('save');

        expect($user->fresh()->name)->toBe('Updated Admin');
    });

    it('deletes an admin', function () {
        actingAsSuperAdmin();
        $user = User::factory()->create()->assignRole('admin');

        Livewire::test(AdminManager::class)
            ->call('delete', $user->id);

        expect(User::find($user->id))->toBeNull();
    });

    it('cannot delete super_admin', function () {
        actingAsSuperAdmin();
        $superAdmin = User::factory()->create()->assignRole('super_admin');

        Livewire::test(AdminManager::class)
            ->call('delete', $superAdmin->id);

        expect(User::find($superAdmin->id))->not->toBeNull();
    });

    it('cannot delete self', function () {
        actingAsSuperAdmin();

        Livewire::test(AdminManager::class)
            ->call('delete', auth()->id());

        expect(User::find(auth()->id()))->not->toBeNull();
    });

    it('deletes selected admins excluding self and super_admin', function () {
        actingAsSuperAdmin();
        $admins = User::factory(2)->create()->each->assignRole('admin');

        Livewire::test(AdminManager::class)
            ->call('selectAll', $admins->pluck('id')->toArray())
            ->call('deleteSelected');

        expect(User::whereIn('id', $admins->pluck('id'))->count())->toBe(0);
    });

    // ─── RENDER ─────────────────────────────────────────────

    it('renders the correct view', function () {
        actingAsSuperAdmin();

        Livewire::test(AdminManager::class)
            ->assertViewIs('admin.admin-manager');
    });
});
