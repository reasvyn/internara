<?php

declare(strict_types=1);

use App\Domain\Admin\Livewire\StudentManager;
use App\Domain\Auth\Enums\Role;
use App\Domain\School\Models\Department;
use App\Domain\User\Models\User;
use Spatie\Permission\Models\Role as RoleModel;

beforeEach(function () {
    app()->setLocale('en');
    collect(Role::cases())->each(fn ($r) => RoleModel::create(['name' => $r->value, 'guard_name' => 'web']));
    $this->admin = User::factory()->create()->assignRole(Role::SUPER_ADMIN->value);
    $this->actingAs($this->admin);
    $this->department = Department::factory()->create();
});

describe('StudentManager', function () {
    it('renders the page', function () {
        Livewire::test(StudentManager::class)
            ->assertSuccessful();
    });

    it('blocks unauthorized users', function () {
        $student = User::factory()->create()->assignRole(Role::STUDENT->value);
        $this->actingAs($student);

        Livewire::test(StudentManager::class)
            ->assertForbidden();
    });

    it('creates a student', function () {
        Livewire::test(StudentManager::class)
            ->call('create')
            ->set('form.name', 'Siswa Baru')
            ->set('form.email', 'siswa@belajar.id')
            ->set('form.national_id_number', '1234567890')
            ->set('form.department_id', $this->department->id)
            ->call('save')
            ->assertHasNoErrors();

        $user = User::where('email', 'siswa@belajar.id')->first();
        expect($user)->not->toBeNull()
            ->and($user->hasRole(Role::STUDENT->value))->toBeTrue();
    });

    it('validates required fields', function () {
        Livewire::test(StudentManager::class)
            ->call('create')
            ->set('form.name', '')
            ->set('form.email', '')
            ->set('form.national_id_number', '')
            ->set('form.department_id', '')
            ->call('save')
            ->assertHasErrors(['form.name', 'form.email', 'form.national_id_number', 'form.department_id']);
    });

    it('edits a student', function () {
        $student = User::factory()->create()->assignRole(Role::STUDENT->value);

        Livewire::test(StudentManager::class)
            ->call('edit', $student->id)
            ->set('form.name', 'Siswa Diupdate')
            ->set('form.national_id_number', '9876543210')
            ->set('form.department_id', $this->department->id)
            ->call('save')
            ->assertHasNoErrors();

        expect($student->fresh()->name)->toBe('Siswa Diupdate');
    });

    it('deletes a student', function () {
        $student = User::factory()->create()->assignRole(Role::STUDENT->value);

        Livewire::test(StudentManager::class)
            ->call('delete', $student->id)
            ->assertHasNoErrors();

        expect(User::find($student->id))->toBeNull();
    });
});
