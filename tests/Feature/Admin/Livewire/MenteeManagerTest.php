<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Livewire;

use App\Domain\Admin\Livewire\MenteeManager;
use App\Domain\Auth\Enums\Role;
use App\Domain\Mentee\Models\Mentee;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role as RoleModel;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    collect(Role::userRoles())->each(fn ($r) => RoleModel::create(['name' => $r->value, 'guard_name' => 'web']));
});

describe('MenteeManager', function () {
    // ─── AUTHORIZATION ───────────────────────────────────────

    it('allows super_admin to render', function () {
        actingAsSuperAdmin();

        Livewire::test(MenteeManager::class)
            ->assertSuccessful();
    });

    it('allows admin to render', function () {
        actingAsAdmin();

        Livewire::test(MenteeManager::class)
            ->assertSuccessful();
    });

    it('denies student to render', function () {
        actingAsStudent();

        Livewire::test(MenteeManager::class)
            ->assertForbidden();
    });

    // ─── HEADERS ────────────────────────────────────────────

    it('returns correct headers', function () {
        actingAsSuperAdmin();

        $headers = Livewire::test(MenteeManager::class)->instance()->headers();

        expect($headers)->toHaveCount(5);
        expect($headers[0]['key'])->toBe('name');
        expect($headers[1]['key'])->toBe('email');
        expect($headers[2]['key'])->toBe('is_active');
    });

    // ─── QUERY ──────────────────────────────────────────────

    it('returns mentee records', function () {
        actingAsSuperAdmin();
        Mentee::factory(3)->create();

        expect(Livewire::test(MenteeManager::class)->instance()->rows()->total())->toBe(3);
    });

    // ─── SEARCH ─────────────────────────────────────────────

    it('filters by search term on user name', function () {
        actingAsSuperAdmin();
        $user = User::factory()->create(['name' => 'MenteeOne']);
        Mentee::factory()->create(['user_id' => $user->id]);
        Mentee::factory(2)->create();

        $component = Livewire::test(MenteeManager::class)
            ->set('search', 'MenteeOne');

        expect($component->instance()->rows()->total())->toBe(1);
    });

    // ─── FILTERS ─────────────────────────────────────────────

    it('filters by is_active', function () {
        actingAsSuperAdmin();
        Mentee::factory(2)->create(['is_active' => true]);
        Mentee::factory()->create(['is_active' => false]);

        $component = Livewire::test(MenteeManager::class)
            ->set('filters.is_active', 'yes');

        expect($component->instance()->rows()->total())->toBe(2);
    });

    it('filters by created date range', function () {
        actingAsSuperAdmin();
        Mentee::factory()->create(['created_at' => now()->subDays(10)]);
        Mentee::factory()->create(['created_at' => now()]);

        $component = Livewire::test(MenteeManager::class)
            ->set('filters.created_from', now()->subDays(5)->format('Y-m-d'))
            ->set('filters.created_to', now()->addDay()->format('Y-m-d'));

        expect($component->instance()->rows()->total())->toBe(1);
    });

    // ─── CRUD ───────────────────────────────────────────────

    it('opens create modal', function () {
        actingAsSuperAdmin();

        Livewire::test(MenteeManager::class)
            ->call('create')
            ->assertSet('userModal', true);
    });

    it('opens edit modal with mentee data', function () {
        actingAsSuperAdmin();
        $mentee = Mentee::factory()->create();

        Livewire::test(MenteeManager::class)
            ->call('edit', $mentee->id)
            ->assertSet('userModal', true)
            ->assertSet('form.name', $mentee->user->name)
            ->assertSet('form.email', $mentee->user->email);
    });

    it('creates a new mentee', function () {
        actingAsSuperAdmin();

        Livewire::test(MenteeManager::class)
            ->set('form.name', 'Mentee Baru')
            ->set('form.email', 'mentee@example.com')
            ->set('form.is_active', true)
            ->call('save');

        expect(Mentee::whereHas('user', fn ($q) => $q->where('email', 'mentee@example.com'))->exists())->toBeTrue();
    });

    it('validates required fields on create', function () {
        actingAsSuperAdmin();

        Livewire::test(MenteeManager::class)
            ->call('save')
            ->assertHasErrors(['form.name', 'form.email']);
    });

    it('updates an existing mentee', function () {
        actingAsSuperAdmin();
        $mentee = Mentee::factory()->create(['is_active' => true]);

        Livewire::test(MenteeManager::class)
            ->call('edit', $mentee->id)
            ->set('form.internal_notes', 'Updated notes')
            ->call('save');

        expect($mentee->fresh()->internal_notes)->toBe('Updated notes');
    });

    it('deletes a mentee', function () {
        actingAsSuperAdmin();
        $mentee = Mentee::factory()->create();

        Livewire::test(MenteeManager::class)
            ->call('delete', $mentee->id);

        expect(Mentee::find($mentee->id))->toBeNull();
    });

    it('deletes selected mentees', function () {
        actingAsSuperAdmin();
        $mentees = Mentee::factory(2)->create();

        Livewire::test(MenteeManager::class)
            ->call('selectAll', $mentees->pluck('id')->toArray())
            ->call('deleteSelected');

        expect(Mentee::whereIn('id', $mentees->pluck('id'))->count())->toBe(0);
    });

    // ─── RENDER ─────────────────────────────────────────────

    it('renders the correct view', function () {
        actingAsSuperAdmin();

        Livewire::test(MenteeManager::class)
            ->assertViewIs('admin.mentee-manager');
    });
});
