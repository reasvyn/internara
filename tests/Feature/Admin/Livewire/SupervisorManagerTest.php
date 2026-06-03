<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Livewire;

use App\Domain\Admin\Livewire\SupervisorManager;
use App\Domain\Auth\Enums\Role;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role as RoleModel;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    collect(Role::userRoles())->each(fn ($r) => RoleModel::create(['name' => $r->value, 'guard_name' => 'web']));
});

describe('SupervisorManager', function () {
    // ─── AUTHORIZATION ───────────────────────────────────────

    it('allows super_admin to render', function () {
        actingAsSuperAdmin();

        Livewire::test(SupervisorManager::class)
            ->assertSuccessful();
    });

    it('allows admin to render', function () {
        actingAsAdmin();

        Livewire::test(SupervisorManager::class)
            ->assertSuccessful();
    });

    it('denies student to render', function () {
        actingAsStudent();

        Livewire::test(SupervisorManager::class)
            ->assertForbidden();
    });

    // ─── HEADERS ────────────────────────────────────────────

    it('returns correct headers', function () {
        actingAsSuperAdmin();

        $headers = Livewire::test(SupervisorManager::class)->instance()->headers();

        expect($headers)->toHaveCount(6);
        expect($headers[0]['key'])->toBe('name');
        expect($headers[1]['key'])->toBe('username');
        expect($headers[2]['key'])->toBe('email');
        expect($headers[3]['key'])->toBe('profile.company_id');
    });

    // ─── QUERY ──────────────────────────────────────────────

    it('only returns users with supervisor role', function () {
        actingAsSuperAdmin();
        User::factory()->create()->assignRole('supervisor');
        User::factory()->create()->assignRole('teacher');
        User::factory()->create()->assignRole('student');

        expect(Livewire::test(SupervisorManager::class)->instance()->rows()->total())->toBe(1);
    });

    // ─── SEARCH ─────────────────────────────────────────────

    it('filters by search term', function () {
        actingAsSuperAdmin();
        User::factory()->create(['name' => 'SupervisorOne'])->assignRole('supervisor');
        User::factory()->create(['name' => 'Other'])->assignRole('supervisor');

        $component = Livewire::test(SupervisorManager::class)
            ->set('search', 'SupervisorOne');

        expect($component->instance()->rows()->total())->toBe(1);
    });

    // ─── FILTERS ─────────────────────────────────────────────

    it('filters by created date range', function () {
        actingAsSuperAdmin();
        User::factory()->create(['created_at' => now()->subDays(10)])->assignRole('supervisor');
        User::factory()->create(['created_at' => now()])->assignRole('supervisor');

        $component = Livewire::test(SupervisorManager::class)
            ->set('filters.created_from', now()->subDays(5)->format('Y-m-d'))
            ->set('filters.created_to', now()->addDay()->format('Y-m-d'));

        expect($component->instance()->rows()->total())->toBe(1);
    });

    // ─── CRUD ───────────────────────────────────────────────

    it('opens create modal', function () {
        actingAsSuperAdmin();

        Livewire::test(SupervisorManager::class)
            ->call('create')
            ->assertSet('userModal', true);
    });

    it('opens edit modal with supervisor data', function () {
        actingAsSuperAdmin();
        $user = User::factory()->create()->assignRole('supervisor');

        Livewire::test(SupervisorManager::class)
            ->call('edit', $user->id)
            ->assertSet('userModal', true)
            ->assertSet('form.name', $user->name)
            ->assertSet('form.email', $user->email);
    });

    it('creates a new supervisor', function () {
        actingAsSuperAdmin();

        Livewire::test(SupervisorManager::class)
            ->set('form.name', 'Supervisor Baru')
            ->set('form.email', 'supervisor@example.com')
            ->set('form.phone', '08123456789')
            ->call('save');

        expect(User::where('email', 'supervisor@example.com')->exists())->toBeTrue();
    });

    it('validates required fields on create', function () {
        actingAsSuperAdmin();

        Livewire::test(SupervisorManager::class)
            ->call('save')
            ->assertHasErrors(['form.name', 'form.email']);
    });

    it('updates an existing supervisor', function () {
        actingAsSuperAdmin();
        $user = User::factory()->create()->assignRole('supervisor');

        Livewire::test(SupervisorManager::class)
            ->call('edit', $user->id)
            ->set('form.name', 'Updated Supervisor')
            ->call('save');

        expect($user->fresh()->name)->toBe('Updated Supervisor');
    });

    it('deletes a supervisor', function () {
        actingAsSuperAdmin();
        $user = User::factory()->create()->assignRole('supervisor');

        Livewire::test(SupervisorManager::class)
            ->call('delete', $user->id);

        expect(User::find($user->id))->toBeNull();
    });

    it('deletes selected supervisors', function () {
        actingAsSuperAdmin();
        $users = User::factory(2)->create()->each->assignRole('supervisor');

        Livewire::test(SupervisorManager::class)
            ->call('selectAll', $users->pluck('id')->toArray())
            ->call('deleteSelected');

        expect(User::whereIn('id', $users->pluck('id'))->count())->toBe(0);
    });

    // ─── RENDER ─────────────────────────────────────────────

    it('renders the correct view', function () {
        actingAsSuperAdmin();

        Livewire::test(SupervisorManager::class)
            ->assertViewIs('admin.supervisor-manager');
    });
});
