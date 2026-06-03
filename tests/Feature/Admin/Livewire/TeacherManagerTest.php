<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Livewire;

use App\Domain\Admin\Livewire\TeacherManager;
use App\Domain\Auth\Enums\Role;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role as RoleModel;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    collect(Role::userRoles())->each(fn ($r) => RoleModel::create(['name' => $r->value, 'guard_name' => 'web']));
});

describe('TeacherManager', function () {
    // ─── AUTHORIZATION ───────────────────────────────────────

    it('allows super_admin to render', function () {
        actingAsSuperAdmin();

        Livewire::test(TeacherManager::class)
            ->assertSuccessful();
    });

    it('allows admin to render', function () {
        actingAsAdmin();

        Livewire::test(TeacherManager::class)
            ->assertSuccessful();
    });

    it('denies student to render', function () {
        actingAsStudent();

        Livewire::test(TeacherManager::class)
            ->assertForbidden();
    });

    // ─── HEADERS ────────────────────────────────────────────

    it('returns correct headers', function () {
        actingAsSuperAdmin();

        $headers = Livewire::test(TeacherManager::class)->instance()->headers();

        expect($headers)->toHaveCount(6);
        expect($headers[0]['key'])->toBe('name');
        expect($headers[1]['key'])->toBe('username');
        expect($headers[2]['key'])->toBe('email');
    });

    // ─── QUERY ──────────────────────────────────────────────

    it('only returns users with teacher role', function () {
        actingAsSuperAdmin();
        User::factory()->create()->assignRole('teacher');
        User::factory()->create()->assignRole('student');
        User::factory()->create()->assignRole('super_admin');

        expect(Livewire::test(TeacherManager::class)->instance()->rows()->total())->toBe(1);
    });

    // ─── SEARCH ─────────────────────────────────────────────

    it('filters by search term', function () {
        actingAsSuperAdmin();
        User::factory()->create(['name' => 'TeacherOne'])->assignRole('teacher');
        User::factory()->create(['name' => 'Other'])->assignRole('teacher');

        $component = Livewire::test(TeacherManager::class)
            ->set('search', 'TeacherOne');

        expect($component->instance()->rows()->total())->toBe(1);
    });

    // ─── FILTERS ─────────────────────────────────────────────

    it('filters by created date range', function () {
        actingAsSuperAdmin();
        User::factory()->create(['created_at' => now()->subDays(10)])->assignRole('teacher');
        User::factory()->create(['created_at' => now()])->assignRole('teacher');

        $component = Livewire::test(TeacherManager::class)
            ->set('filters.created_from', now()->subDays(5)->format('Y-m-d'))
            ->set('filters.created_to', now()->addDay()->format('Y-m-d'));

        expect($component->instance()->rows()->total())->toBe(1);
    });

    // ─── CRUD ───────────────────────────────────────────────

    it('opens create modal', function () {
        actingAsSuperAdmin();

        Livewire::test(TeacherManager::class)
            ->call('create')
            ->assertSet('userModal', true);
    });

    it('opens edit modal with teacher data', function () {
        actingAsSuperAdmin();
        $user = User::factory()->create()->assignRole('teacher');

        Livewire::test(TeacherManager::class)
            ->call('edit', $user->id)
            ->assertSet('userModal', true)
            ->assertSet('form.name', $user->name)
            ->assertSet('form.email', $user->email);
    });

    it('creates a new teacher', function () {
        actingAsSuperAdmin();

        Livewire::test(TeacherManager::class)
            ->set('form.name', 'Teacher Baru')
            ->set('form.email', 'teacher@example.com')
            ->set('form.employee_id_number', 'NIP-001')
            ->call('save');

        expect(User::where('email', 'teacher@example.com')->exists())->toBeTrue();
    });

    it('validates required fields on create', function () {
        actingAsSuperAdmin();

        Livewire::test(TeacherManager::class)
            ->call('save')
            ->assertHasErrors(['form.name', 'form.email']);
    });

    it('updates an existing teacher', function () {
        actingAsSuperAdmin();
        $user = User::factory()->create()->assignRole('teacher');

        Livewire::test(TeacherManager::class)
            ->call('edit', $user->id)
            ->set('form.name', 'Updated Teacher')
            ->call('save');

        expect($user->fresh()->name)->toBe('Updated Teacher');
    });

    it('deletes a teacher', function () {
        actingAsSuperAdmin();
        $user = User::factory()->create()->assignRole('teacher');

        Livewire::test(TeacherManager::class)
            ->call('delete', $user->id);

        expect(User::find($user->id))->toBeNull();
    });

    it('deletes selected teachers', function () {
        actingAsSuperAdmin();
        $users = User::factory(2)->create()->each->assignRole('teacher');

        Livewire::test(TeacherManager::class)
            ->call('selectAll', $users->pluck('id')->toArray())
            ->call('deleteSelected');

        expect(User::whereIn('id', $users->pluck('id'))->count())->toBe(0);
    });

    // ─── RENDER ─────────────────────────────────────────────

    it('renders the correct view', function () {
        actingAsSuperAdmin();

        Livewire::test(TeacherManager::class)
            ->assertViewIs('admin.teacher-manager');
    });
});
