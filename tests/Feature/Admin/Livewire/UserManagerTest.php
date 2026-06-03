<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Livewire;

use App\Domain\Admin\Livewire\UserManager;
use App\Domain\Auth\Enums\AccountStatus;
use App\Domain\Auth\Enums\Role;
use App\Domain\Auth\Models\ActivationToken;
use App\Domain\Shared\Support\CsvHandler;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Livewire;
use Spatie\Permission\Models\Role as RoleModel;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\StreamedResponse;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    collect(Role::userRoles())->each(fn ($r) => RoleModel::create(['name' => $r->value, 'guard_name' => 'web']));
});

describe('UserManager', function () {
    // ─── AUTHORIZATION ───────────────────────────────────────

    it('allows super_admin to render', function () {
        actingAsSuperAdmin();

        Livewire::test(UserManager::class)
            ->assertSuccessful();
    });

    it('allows admin to render', function () {
        actingAsAdmin();

        Livewire::test(UserManager::class)
            ->assertSuccessful();
    });

    it('denies student to render', function () {
        actingAsStudent();

        Livewire::test(UserManager::class)
            ->assertForbidden();
    });

    // ─── HEADERS ────────────────────────────────────────────

    it('returns correct headers', function () {
        actingAsSuperAdmin();

        $component = Livewire::test(UserManager::class);

        $headers = $component->instance()->headers();

        expect($headers)->toHaveCount(6);
        expect($headers[0]['key'])->toBe('name');
        expect($headers[1]['key'])->toBe('email');
        expect($headers[4]['key'])->toBe('status');
        expect($headers[5]['key'])->toBe('actions');
    });

    // ─── ROWS / QUERY ──────────────────────────────────────

    it('returns paginated rows', function () {
        actingAsSuperAdmin();
        User::factory(15)->create();

        $component = Livewire::test(UserManager::class);

        $rows = $component->instance()->rows();
        expect($rows)->toBeInstanceOf(LengthAwarePaginator::class);
        expect($rows->total())->toBe(16); // +1 from actingAsSuperAdmin
    });

    it('respects perPage option', function () {
        actingAsSuperAdmin();
        User::factory(25)->create();

        $component = Livewire::test(UserManager::class)
            ->set('perPage', 10);

        expect($component->instance()->rows()->count())->toBe(10);
    });

    // ─── SEARCH ─────────────────────────────────────────────

    it('filters rows by search term on name', function () {
        actingAsSuperAdmin();
        User::factory()->create(['name' => 'FindMePlease']);
        User::factory(5)->create();

        $component = Livewire::test(UserManager::class)
            ->set('search', 'FindMePlease');

        expect($component->instance()->rows()->total())->toBe(1);
    });

    it('filters rows by search term on email', function () {
        actingAsSuperAdmin();
        User::factory()->create(['email' => 'unique@example.com']);
        User::factory(5)->create();

        $component = Livewire::test(UserManager::class)
            ->set('search', 'unique@example.com');

        expect($component->instance()->rows()->total())->toBe(1);
    });

    it('resets pagination on search update', function () {
        actingAsSuperAdmin();
        User::factory(25)->create();

        $component = Livewire::test(UserManager::class)
            ->set('perPage', 10)
            ->set('search', 'nonexistent');

        expect($component->instance()->rows()->total())->toBe(0);
    });

    // ─── FILTERS ─────────────────────────────────────────────

    it('filters by role', function () {
        actingAsSuperAdmin();
        $student = User::factory()->create()->assignRole('student');
        User::factory()->create()->assignRole('teacher');

        $component = Livewire::test(UserManager::class)
            ->set('filters.role', 'student');

        $ids = $component->instance()->rows()->pluck('id')->toArray();
        expect($ids)->toContain($student->id);
    });

    it('filters by status', function () {
        actingAsSuperAdmin();
        $user = User::factory()->create();
        $user->statuses()->create(['name' => AccountStatus::SUSPENDED->value]);
        User::factory()->create();

        $component = Livewire::test(UserManager::class)
            ->set('filters.status', AccountStatus::SUSPENDED->value);

        expect($component->instance()->rows()->total())->toBeGreaterThanOrEqual(1);
    });

    it('filters by created date range', function () {
        actingAsSuperAdmin();
        User::factory()->create(['created_at' => now()->subDays(10)]);
        User::factory()->create(['created_at' => now()->subDays(2)]);

        $component = Livewire::test(UserManager::class)
            ->set('filters.created_from', now()->subDays(5)->format('Y-m-d'))
            ->set('filters.created_to', now()->subDays(3)->format('Y-m-d'));

        expect($component->instance()->rows()->total())->toBe(0);
    });

    it('resets filters', function () {
        actingAsSuperAdmin();
        User::factory(3)->create();

        $component = Livewire::test(UserManager::class)
            ->set('filters.role', 'student')
            ->call('resetFilters');

        expect($component->instance()->filters)->toBe([]);
    });

    // ─── CREATE MODAL ───────────────────────────────────────

    it('opens create modal', function () {
        actingAsSuperAdmin();

        Livewire::test(UserManager::class)
            ->call('createUser')
            ->assertSet('userModal', true);
    });

    // ─── EDIT MODAL ─────────────────────────────────────────

    it('opens edit modal with user data', function () {
        actingAsSuperAdmin();
        $user = User::factory()->create();

        Livewire::test(UserManager::class)
            ->call('editUser', $user->id)
            ->assertSet('userModal', true)
            ->assertSet('form.name', $user->name)
            ->assertSet('form.email', $user->email);
    });

    it('cannot edit super_admin', function () {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        actingAsSuperAdmin();
        $user = User::factory()->create()->assignRole('super_admin');

        $component = Livewire::test(UserManager::class);
        $component->call('editUser', $user->id);
        expect($component->instance()->userModal)->toBeFalse();
    });

    // ─── SAVE (CREATE) ──────────────────────────────────────

    it('creates a new user', function () {
        actingAsSuperAdmin();

        Livewire::test(UserManager::class)
            ->set('form.name', 'New User')
            ->set('form.email', 'new@example.com')
            ->set('form.roles', ['student'])
            ->call('saveUser')
            ->assertSet('userModal', false);

        expect(User::where('email', 'new@example.com')->exists())->toBeTrue();
    });

    it('validates required fields on create', function () {
        actingAsSuperAdmin();

        Livewire::test(UserManager::class)
            ->call('saveUser')
            ->assertHasErrors(['form.name', 'form.email', 'form.roles']);
    });

    // ─── SAVE (UPDATE) ──────────────────────────────────────

    it('updates an existing user', function () {
        actingAsSuperAdmin();
        $user = User::factory()->create();

        Livewire::test(UserManager::class)
            ->call('editUser', $user->id)
            ->set('form.name', 'UpdatedName')
            ->set('form.roles', ['student'])
            ->call('saveUser')
            ->assertSet('userModal', false);

        expect($user->fresh()->name)->toBe('UpdatedName');
    });

    it('validates unique email on update', function () {
        actingAsSuperAdmin();
        User::factory()->create(['email' => 'existing@example.com']);
        $user = User::factory()->create(['email' => 'user@example.com']);

        Livewire::test(UserManager::class)
            ->call('editUser', $user->id)
            ->set('form.email', 'existing@example.com')
            ->call('saveUser')
            ->assertHasErrors(['form.email']);
    });

    // ─── DELETE ─────────────────────────────────────────────

    it('deletes a user', function () {
        actingAsSuperAdmin();
        $user = User::factory()->create();

        Livewire::test(UserManager::class)
            ->call('deleteUser', $user->id);

        expect(User::find($user->id))->toBeNull();
    });

    it('cannot delete super_admin', function () {
        actingAsSuperAdmin();
        $superAdmin = User::factory()->create()->assignRole('super_admin');

        Livewire::test(UserManager::class)
            ->call('deleteUser', $superAdmin->id);

        expect(User::find($superAdmin->id))->not->toBeNull();
    });

    // ─── BULK DELETE ────────────────────────────────────────

    it('deletes selected users', function () {
        actingAsSuperAdmin();
        $users = User::factory(3)->create();

        Livewire::test(UserManager::class)
            ->call('selectAll', $users->pluck('id')->toArray())
            ->call('deleteSelected');

        expect(User::whereIn('id', $users->pluck('id'))->count())->toBe(0);
    });

    it('warns when no records selected for bulk action', function () {
        actingAsSuperAdmin();

        Livewire::test(UserManager::class)
            ->call('deleteSelected')
            ->assertSet('selectedIds', []);
    });

    // ─── LOCK / UNLOCK ──────────────────────────────────────

    it('locks selected users', function () {
        actingAsSuperAdmin();
        $users = User::factory(2)->create();

        Livewire::test(UserManager::class)
            ->call('selectAll', $users->pluck('id')->toArray())
            ->call('lockSelected');

        foreach ($users as $user) {
            expect($user->fresh()->statuses()->latest('id')->first()->name)->toBe(AccountStatus::SUSPENDED->value);
        }
    });

    it('unlocks selected users', function () {
        actingAsSuperAdmin();
        $users = User::factory(2)->create();

        Livewire::test(UserManager::class)
            ->call('selectAll', $users->pluck('id')->toArray())
            ->call('lockSelected')
            ->call('selectAll', $users->pluck('id')->toArray())
            ->call('unlockSelected');

        foreach ($users as $user) {
            expect($user->fresh()->statuses()->latest('id')->first()->name)->toBe(AccountStatus::ACTIVATED->value);
        }
    });

    // ─── RESET PASSWORD ─────────────────────────────────────

    it('reset password revokes activation tokens', function () {
        actingAsSuperAdmin();
        $user = User::factory()->create(['setup_required' => true]);

        Livewire::test(UserManager::class)
            ->call('resetPassword', $user->id);

        expect(ActivationToken::where('user_id', $user->id)->exists())->toBeFalse();
    });

    // ─── COMPUTED PROPERTIES ────────────────────────────────

    it('returns available roles excluding super_admin and admin', function () {
        actingAsSuperAdmin();

        $component = Livewire::test(UserManager::class);
        $roles = $component->instance()->roles();

        expect($roles->pluck('name')->toArray())->not->toContain('super_admin', 'admin');
    });

    it('returns status options without PROTECTED or ARCHIVED', function () {
        actingAsSuperAdmin();

        $component = Livewire::test(UserManager::class);
        $options = $component->instance()->statusOptions();

        $values = array_column($options, 'id');
        expect($values)->not->toContain(AccountStatus::PROTECTED->value, AccountStatus::ARCHIVED->value);
    });

    it('returns stats', function () {
        actingAsSuperAdmin();

        $component = Livewire::test(UserManager::class);
        $stats = $component->instance()->stats();

        expect($stats)->toBeArray();
    });

    // ─── EXPORT ─────────────────────────────────────────────

    it('export returns StreamedResponse', function () {
        actingAsSuperAdmin();
        User::factory(3)->create();

        $response = Livewire::test(UserManager::class)
            ->instance()
            ->export(app(CsvHandler::class));

        expect($response)->toBeInstanceOf(StreamedResponse::class);
    });

    it('exportSelected returns null when no selection', function () {
        actingAsSuperAdmin();

        $instance = Livewire::test(UserManager::class)->instance();
        $result = $instance->exportSelected(app(CsvHandler::class));

        expect($result)->toBeNull();
    });

    it('exportSelected returns StreamedResponse when selection exists', function () {
        actingAsSuperAdmin();
        $users = User::factory(2)->create();

        $instance = Livewire::test(UserManager::class)
            ->call('selectAll', $users->pluck('id')->toArray())
            ->instance();

        $result = $instance->exportSelected(app(CsvHandler::class));
        expect($result)->toBeInstanceOf(StreamedResponse::class);
    });

    it('downloadTemplate returns StreamedResponse', function () {
        actingAsSuperAdmin();

        $response = Livewire::test(UserManager::class)
            ->instance()
            ->downloadTemplate(app(CsvHandler::class));

        expect($response)->toBeInstanceOf(StreamedResponse::class);
    });

    // ─── RENDER ─────────────────────────────────────────────

    it('renders the correct view', function () {
        actingAsSuperAdmin();

        Livewire::test(UserManager::class)
            ->assertViewIs('admin.manager');
    });
});
