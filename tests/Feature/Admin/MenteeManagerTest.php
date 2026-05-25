<?php

declare(strict_types=1);

use App\Domain\Admin\Livewire\MenteeManager;
use App\Domain\Auth\Enums\Role;
use App\Domain\Mentee\Models\Mentee;
use App\Domain\User\Models\User;
use Spatie\Permission\Models\Role as RoleModel;

beforeEach(function () {
    app()->setLocale('en');
    collect(Role::cases())->each(fn ($r) => RoleModel::create(['name' => $r->value, 'guard_name' => 'web']));
    $this->admin = User::factory()->create()->assignRole(Role::SUPER_ADMIN->value);
    $this->actingAs($this->admin);
});

describe('MenteeManager', function () {
    it('renders the page', function () {
        Livewire::test(MenteeManager::class)
            ->assertSuccessful();
    });

    it('blocks unauthorized users', function () {
        $student = User::factory()->create()->assignRole(Role::STUDENT->value);
        $this->actingAs($student);

        Livewire::test(MenteeManager::class)
            ->assertForbidden();
    });

    it('creates a mentee', function () {
        Livewire::test(MenteeManager::class)
            ->call('create')
            ->set('form.name', 'Mentee Satu')
            ->set('form.email', 'mentee@belajar.id')
            ->set('form.is_active', true)
            ->call('save')
            ->assertHasNoErrors();

        expect(Mentee::whereHas('user', fn ($q) => $q->where('email', 'mentee@belajar.id'))->exists())->toBeTrue();
    });

    it('validates required fields', function () {
        Livewire::test(MenteeManager::class)
            ->call('create')
            ->set('form.name', '')
            ->set('form.email', '')
            ->call('save')
            ->assertHasErrors(['form.name', 'form.email']);
    });

    it('edits a mentee', function () {
        $mentee = Mentee::factory()->create();

        Livewire::test(MenteeManager::class)
            ->call('edit', $mentee->id)
            ->set('form.internal_notes', 'Test notes')
            ->set('form.is_active', false)
            ->call('save')
            ->assertHasNoErrors();

        $mentee->refresh();
        expect($mentee->internal_notes)->toBe('Test notes')
            ->and($mentee->is_active)->toBeFalse();
    });

    it('deletes a mentee', function () {
        $mentee = Mentee::factory()->create();

        Livewire::test(MenteeManager::class)
            ->call('delete', $mentee->id)
            ->assertHasNoErrors();

        expect(Mentee::find($mentee->id))->toBeNull();
    });
});
