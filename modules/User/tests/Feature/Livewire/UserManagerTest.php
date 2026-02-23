<?php

declare(strict_types=1);

namespace Modules\User\Tests\Feature\Livewire;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Modules\Permission\Database\Seeders\PermissionSeeder;
use Modules\Permission\Database\Seeders\RoleSeeder;
use Modules\User\Livewire\UserManager;
use Modules\User\Models\User;
use Tests\TestCase;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
    $this->seed(RoleSeeder::class);
});

describe('UserManager Component', function () {
    test('it renders correctly for authorized users', function () {
        $admin = User::factory()->create();
        $admin->givePermissionTo('user.view');
        $this->actingAs($admin);

        Livewire::test(UserManager::class)
            ->assertStatus(200)
            ->assertSee(__('user::ui.manager.title'));
    });

    test('it can create a student user', function () {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $test = Livewire::test(UserManager::class)
            ->set('targetRole', 'student')
            ->call('add')
            ->set('form.name', 'Student User')
            ->set('form.email', 'student@internara.test')
            ->set('form.roles', ['student'])
            ->set('form.status', 'active')
            ->set('form.password', 'Password123!')
            ->set('form.password_confirmation', 'Password123!')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('formModal', false);

        $user = User::where('email', 'student@internara.test')->first();
        expect($user)->not->toBeNull()
            ->and($user->hasRole('student'))->toBeTrue();
    });

    test('it protects super-admins from deletion', function () {
        $admin = User::factory()->create();
        $admin->givePermissionTo(['user.view', 'user.manage']);
        $this->actingAs($admin);

        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super-admin');

        Livewire::test(UserManager::class)
            ->set('selectedIds', [$superAdmin->id])
            ->call('removeSelected')
            ->assertHasNoErrors();

        expect(User::find($superAdmin->id))->not->toBeNull();
    });
});
