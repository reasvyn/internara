<?php

declare(strict_types=1);

namespace Modules\Student\Tests\Feature\Livewire;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Modules\Permission\Database\Seeders\PermissionSeeder;
use Modules\Permission\Database\Seeders\RoleSeeder;
use Modules\Student\Livewire\StudentManager;
use Modules\User\Models\User;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
    $this->seed(RoleSeeder::class);
});

describe('StudentManager Component', function () {
    test('it renders correctly for authorized users', function () {
        $admin = User::factory()->create();
        $admin->givePermissionTo('student.manage');
        $this->actingAs($admin);

        Livewire::test(StudentManager::class)
            ->assertStatus(200)
            ->assertSee(__('admin::ui.menu.students'));
    });

    test('it aborts for unauthorized users', function () {
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(StudentManager::class)
            ->assertForbidden();
    });

    test('it can create a student user', function () {
        $admin = User::factory()->create();
        $admin->givePermissionTo('student.manage');
        $this->actingAs($admin);

        Livewire::test(StudentManager::class)
            ->call('add')
            ->set('form.name', 'New Student')
            ->set('form.email', 'new.student@internara.test')
            ->set('form.username', 'newstudent123')
            ->set('form.roles', ['student'])
            ->set('form.status', 'active')
            ->set('form.password', 'Password123!')
            ->set('form.password_confirmation', 'Password123!')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('formModal', false);

        $user = User::where('email', 'new.student@internara.test')->first();
        expect($user)->not->toBeNull()->and($user->hasRole('student'))->toBeTrue();
    });

    test('it can edit an existing student user', function () {
        $admin = User::factory()->create();
        $admin->givePermissionTo('student.manage');
        $this->actingAs($admin);

        $student = User::factory()->create();
        $student->assignRole('student');

        Livewire::test(StudentManager::class)
            ->call('edit', $student->id)
            ->assertSet('formModal', true)
            ->assertSet('form.name', $student->name)
            ->assertSet('form.email', $student->email)
            ->set('form.name', 'Updated Student Name')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('formModal', false);

        expect($student->fresh()->name)->toBe('Updated Student Name');
    });

    test('it validates form inputs', function () {
        $admin = User::factory()->create();
        $admin->givePermissionTo('student.manage');
        $this->actingAs($admin);

        Livewire::test(StudentManager::class)
            ->call('add')
            ->set('form.name', '')
            ->set('form.email', 'invalid-email')
            ->call('save')
            ->assertHasErrors(['form.name' => 'required', 'form.email' => 'email']);
    });
});
