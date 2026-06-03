<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Livewire;

use App\Domain\Admin\Livewire\MentorManager;
use App\Domain\Auth\Enums\Role;
use App\Domain\Mentor\Models\Mentor;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role as RoleModel;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    collect(Role::userRoles())->each(fn ($r) => RoleModel::create(['name' => $r->value, 'guard_name' => 'web']));
});

describe('MentorManager', function () {
    // ─── AUTHORIZATION ───────────────────────────────────────

    it('allows super_admin to render', function () {
        actingAsSuperAdmin();

        Livewire::test(MentorManager::class)
            ->assertSuccessful();
    });

    it('allows admin to render', function () {
        actingAsAdmin();

        Livewire::test(MentorManager::class)
            ->assertSuccessful();
    });

    it('denies student to render', function () {
        actingAsStudent();

        Livewire::test(MentorManager::class)
            ->assertForbidden();
    });

    // ─── HEADERS ────────────────────────────────────────────

    it('returns correct headers', function () {
        actingAsSuperAdmin();

        $headers = Livewire::test(MentorManager::class)->instance()->headers();

        expect($headers)->toHaveCount(6);
        expect($headers[0]['key'])->toBe('name');
        expect($headers[1]['key'])->toBe('email');
        expect($headers[2]['key'])->toBe('type');
        expect($headers[3]['key'])->toBe('is_active');
    });

    // ─── QUERY ──────────────────────────────────────────────

    it('returns mentor records', function () {
        actingAsSuperAdmin();
        Mentor::factory(3)->create();

        expect(Livewire::test(MentorManager::class)->instance()->rows()->total())->toBe(3);
    });

    // ─── SEARCH ─────────────────────────────────────────────

    it('filters by search term on user name', function () {
        actingAsSuperAdmin();
        $user = User::factory()->create(['name' => 'MentorOne']);
        Mentor::factory()->create(['user_id' => $user->id]);
        Mentor::factory(2)->create();

        $component = Livewire::test(MentorManager::class)
            ->set('search', 'MentorOne');

        expect($component->instance()->rows()->total())->toBe(1);
    });

    // ─── FILTERS ─────────────────────────────────────────────

    it('filters by type', function () {
        actingAsSuperAdmin();
        Mentor::factory()->schoolTeacher()->create();
        Mentor::factory()->industrySupervisor()->create();

        $component = Livewire::test(MentorManager::class)
            ->set('filters.type', Mentor::TYPE_SCHOOL_TEACHER);

        expect($component->instance()->rows()->total())->toBe(1);
    });

    it('filters by is_active', function () {
        actingAsSuperAdmin();
        Mentor::factory(2)->create(['is_active' => true]);
        Mentor::factory()->create(['is_active' => false]);

        $component = Livewire::test(MentorManager::class)
            ->set('filters.is_active', 'yes');

        expect($component->instance()->rows()->total())->toBe(2);
    });

    it('filters by created date range', function () {
        actingAsSuperAdmin();
        Mentor::factory()->create(['created_at' => now()->subDays(10)]);
        Mentor::factory()->create(['created_at' => now()]);

        $component = Livewire::test(MentorManager::class)
            ->set('filters.created_from', now()->subDays(5)->format('Y-m-d'))
            ->set('filters.created_to', now()->addDay()->format('Y-m-d'));

        expect($component->instance()->rows()->total())->toBe(1);
    });

    // ─── CRUD ───────────────────────────────────────────────

    it('opens create modal', function () {
        actingAsSuperAdmin();

        Livewire::test(MentorManager::class)
            ->call('create')
            ->assertSet('userModal', true);
    });

    it('opens edit modal with mentor data', function () {
        actingAsSuperAdmin();
        $mentor = Mentor::factory()->create();

        Livewire::test(MentorManager::class)
            ->call('edit', $mentor->id)
            ->assertSet('userModal', true)
            ->assertSet('form.name', $mentor->user->name)
            ->assertSet('form.email', $mentor->user->email)
            ->assertSet('form.type', $mentor->type);
    });

    it('creates a new mentor', function () {
        actingAsSuperAdmin();

        Livewire::test(MentorManager::class)
            ->set('form.name', 'Mentor Baru')
            ->set('form.email', 'mentor@example.com')
            ->set('form.type', Mentor::TYPE_SCHOOL_TEACHER)
            ->set('form.is_active', true)
            ->call('save');

        expect(Mentor::whereHas('user', fn ($q) => $q->where('email', 'mentor@example.com'))->exists())->toBeTrue();
    });

    it('validates required fields on create', function () {
        actingAsSuperAdmin();

        Livewire::test(MentorManager::class)
            ->call('save')
            ->assertHasErrors(['form.name', 'form.email', 'form.type']);
    });

    it('updates an existing mentor', function () {
        actingAsSuperAdmin();
        $mentor = Mentor::factory()->create(['is_active' => true]);

        Livewire::test(MentorManager::class)
            ->call('edit', $mentor->id)
            ->set('form.is_active', false)
            ->call('save');

        expect($mentor->fresh()->is_active)->toBeFalse();
    });

    it('deletes a mentor', function () {
        actingAsSuperAdmin();
        $mentor = Mentor::factory()->create();

        Livewire::test(MentorManager::class)
            ->call('delete', $mentor->id);

        expect(Mentor::find($mentor->id))->toBeNull();
    });

    it('deletes selected mentors', function () {
        actingAsSuperAdmin();
        $mentors = Mentor::factory(2)->create();

        Livewire::test(MentorManager::class)
            ->call('selectAll', $mentors->pluck('id')->toArray())
            ->call('deleteSelected');

        expect(Mentor::whereIn('id', $mentors->pluck('id'))->count())->toBe(0);
    });

    // ─── RENDER ─────────────────────────────────────────────

    it('renders the correct view', function () {
        actingAsSuperAdmin();

        Livewire::test(MentorManager::class)
            ->assertViewIs('admin.mentor-manager');
    });
});
