<?php

declare(strict_types=1);

use App\Domain\Admin\Livewire\TeacherManager;
use App\Domain\Auth\Enums\Role;
use App\Domain\User\Models\User;
use Spatie\Permission\Models\Role as RoleModel;

beforeEach(function () {
    app()->setLocale('en');
    collect(Role::cases())->each(fn ($r) => RoleModel::create(['name' => $r->value, 'guard_name' => 'web']));
    $this->admin = User::factory()->create()->assignRole(Role::SUPER_ADMIN->value);
    $this->actingAs($this->admin);
});

describe('TeacherManager', function () {
    it('renders the page', function () {
        Livewire::test(TeacherManager::class)
            ->assertSuccessful();
    });

    it('blocks unauthorized users', function () {
        $student = User::factory()->create()->assignRole(Role::STUDENT->value);
        $this->actingAs($student);

        Livewire::test(TeacherManager::class)
            ->assertForbidden();
    });

    it('creates a teacher', function () {
        Livewire::test(TeacherManager::class)
            ->call('create')
            ->set('form.name', 'Pak Guru')
            ->set('form.email', 'guru@sekolah.sch.id')
            ->set('form.nip', '198004052010011001')
            ->call('save')
            ->assertHasNoErrors();

        $user = User::where('email', 'guru@sekolah.sch.id')->first();
        expect($user)->not->toBeNull()
            ->and($user->hasRole(Role::TEACHER->value))->toBeTrue();
    });

    it('validates required fields', function () {
        Livewire::test(TeacherManager::class)
            ->call('create')
            ->set('form.name', '')
            ->set('form.email', '')
            ->call('save')
            ->assertHasErrors(['form.name', 'form.email']);
    });

    it('edits a teacher', function () {
        $teacher = User::factory()->create()->assignRole(Role::TEACHER->value);

        Livewire::test(TeacherManager::class)
            ->call('edit', $teacher->id)
            ->set('form.name', 'Guru Diupdate')
            ->call('save')
            ->assertHasNoErrors();

        expect($teacher->fresh()->name)->toBe('Guru Diupdate');
    });

    it('deletes a teacher', function () {
        $teacher = User::factory()->create()->assignRole(Role::TEACHER->value);

        Livewire::test(TeacherManager::class)
            ->call('delete', $teacher->id)
            ->assertHasNoErrors();

        expect(User::find($teacher->id))->toBeNull();
    });
});
