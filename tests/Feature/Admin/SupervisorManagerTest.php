<?php

declare(strict_types=1);

use App\Domain\Admin\Livewire\SupervisorManager;
use App\Domain\Auth\Enums\Role;
use App\Domain\User\Models\User;
use Spatie\Permission\Models\Role as RoleModel;

beforeEach(function () {
    app()->setLocale('en');
    collect(Role::cases())->each(fn ($r) => RoleModel::create(['name' => $r->value, 'guard_name' => 'web']));
    $this->admin = User::factory()->create()->assignRole(Role::SUPER_ADMIN->value);
    $this->actingAs($this->admin);
});

describe('SupervisorManager', function () {
    it('renders the page', function () {
        Livewire::test(SupervisorManager::class)
            ->assertSuccessful();
    });

    it('blocks unauthorized users', function () {
        $student = User::factory()->create()->assignRole(Role::STUDENT->value);
        $this->actingAs($student);

        Livewire::test(SupervisorManager::class)
            ->assertForbidden();
    });

    it('creates a supervisor', function () {
        Livewire::test(SupervisorManager::class)
            ->call('create')
            ->set('form.name', 'Supervisor Satu')
            ->set('form.email', 'supervisor@mitra.id')
            ->call('save')
            ->assertHasNoErrors();

        $user = User::where('email', 'supervisor@mitra.id')->first();
        expect($user)->not->toBeNull()
            ->and($user->hasRole(Role::SUPERVISOR->value))->toBeTrue();
    });

    it('validates required fields', function () {
        Livewire::test(SupervisorManager::class)
            ->call('create')
            ->set('form.name', '')
            ->set('form.email', '')
            ->call('save')
            ->assertHasErrors(['form.name', 'form.email']);
    });

    it('edits a supervisor', function () {
        $supervisor = User::factory()->create()->assignRole(Role::SUPERVISOR->value);

        Livewire::test(SupervisorManager::class)
            ->call('edit', $supervisor->id)
            ->set('form.name', 'Supervisor Diupdate')
            ->call('save')
            ->assertHasNoErrors();

        expect($supervisor->fresh()->name)->toBe('Supervisor Diupdate');
    });

    it('deletes a supervisor', function () {
        $supervisor = User::factory()->create()->assignRole(Role::SUPERVISOR->value);

        Livewire::test(SupervisorManager::class)
            ->call('delete', $supervisor->id)
            ->assertHasNoErrors();

        expect(User::find($supervisor->id))->toBeNull();
    });
});
