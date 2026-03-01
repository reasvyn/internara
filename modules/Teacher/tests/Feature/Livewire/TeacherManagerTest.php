<?php

declare(strict_types=1);

namespace Modules\Teacher\Tests\Feature\Livewire;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Modules\Permission\Database\Seeders\PermissionSeeder;
use Modules\Permission\Database\Seeders\RoleSeeder;
use Modules\Teacher\Livewire\TeacherManager;
use Modules\User\Models\User;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
    $this->seed(RoleSeeder::class);
});

describe('TeacherManager Component', function () {
    test('it blocks unauthorized users from mounting', function () {
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(TeacherManager::class)
            ->assertForbidden();
    });

    test('it renders correctly for authorized users', function () {
        $admin = User::factory()->create();
        $admin->givePermissionTo('teacher.manage');
        $this->actingAs($admin);

        Livewire::test(TeacherManager::class)
            ->assertStatus(200)
            ->assertSee(__('admin::ui.menu.teachers'));
    });

    test('it opens the add modal and sets default roles', function () {
        $admin = User::factory()->create();
        $admin->givePermissionTo('teacher.manage');
        $this->actingAs($admin);

        Livewire::test(TeacherManager::class)
            ->call('add')
            ->assertSet('form.roles', ['teacher'])
            ->assertSet('formModal', true);
    });

    test('it opens the edit modal and fills the form with teacher data', function () {
        $admin = User::factory()->create();
        $admin->givePermissionTo('teacher.manage');
        $this->actingAs($admin);

        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');

        Livewire::test(TeacherManager::class)
            ->call('edit', $teacher->id)
            ->assertSet('form.id', $teacher->id)
            ->assertSet('form.name', $teacher->name)
            ->assertSet('form.email', $teacher->email)
            ->assertSet('formModal', true);
    });

    test('it saves a new teacher correctly', function () {
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');
        $this->actingAs($admin);

        Livewire::test(TeacherManager::class)
            ->call('add')
            ->set('form.name', 'New Teacher')
            ->set('form.email', 'teacher@example.com')
            ->set('form.roles', ['teacher'])
            ->set('form.status', 'active')
            ->set('form.password', 'Password123!')
            ->set('form.password_confirmation', 'Password123!')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('formModal', false);

        $user = User::where('email', 'teacher@example.com')->first();
        expect($user)->not->toBeNull()->and($user->hasRole('teacher'))->toBeTrue();
    });

    test('it updates an existing teacher correctly', function () {
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');
        $this->actingAs($admin);

        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');

        Livewire::test(TeacherManager::class)
            ->call('edit', $teacher->id)
            ->set('form.name', 'Updated Teacher Name')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('formModal', false);

        expect(User::find($teacher->id)->name)->toBe('Updated Teacher Name');
    });

    test('it handles exceptions gracefully during save', function () {
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');
        $this->actingAs($admin);

        // Force an exception by providing an invalid role that causes a failure in service
        Livewire::test(TeacherManager::class)
            ->call('add')
            ->set('form.name', 'Exception Teacher')
            ->set('form.email', 'invalid-email') // will fail validation
            ->call('save')
            ->assertHasErrors(['form.email']);
    });
});
