<?php

declare(strict_types=1);

namespace Modules\Auth\Tests\Feature\Registration\Livewire;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Modules\Auth\Registration\Livewire\RegisterSuperAdmin;
use Modules\Permission\Database\Seeders\PermissionSeeder;
use Modules\Permission\Database\Seeders\RoleSeeder;
use Modules\User\Models\User;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
    $this->seed(RoleSeeder::class);
});

describe('RegisterSuperAdmin Component', function () {
    test('it renders correctly', function () {
        Livewire::test(RegisterSuperAdmin::class)
            ->assertStatus(200)
            ->assertSee(__('auth::ui.register_super_admin.title'))
            ->assertSee('Administrator');
    });

    test('it can register a super admin successfully', function () {
        Livewire::test(RegisterSuperAdmin::class)
            ->set('form.name', 'System Admin')
            ->set('form.email', 'admin@internara.test')
            ->set('form.password', 'password123')
            ->set('form.password_confirmation', 'password123')
            ->call('register')
            ->assertHasNoErrors()
            ->assertDispatched('super_admin_registered');

        $user = User::where('email', 'admin@internara.test')->first();
        expect($user)->not->toBeNull()->and($user->hasRole('super-admin'))->toBeTrue();
    });

    test('it validates password requirements [SYRS-NF-501]', function () {
        Livewire::test(RegisterSuperAdmin::class)
            ->set('form.password', '123')
            ->set('form.password_confirmation', '123')
            ->call('register')
            ->assertHasErrors(['form.password']);
    });

    test('it supports account re-linking during setup', function () {
        app(\Modules\Setting\Services\Contracts\SettingService::class)->setValue(
            'app_installed',
            false,
        );

        // Pre-create user
        $existing = User::factory()->create([
            'email' => 'link@internara.test',
            'name' => 'Old Name',
        ]);

        Livewire::test(RegisterSuperAdmin::class)
            ->set('form.email', 'link@internara.test')
            ->set('form.name', 'New Name')
            ->set('form.password', 'password123')
            ->set('form.password_confirmation', 'password123')
            ->call('register')
            ->assertHasNoErrors();

        // Should update existing record instead of failing with "email taken"
        expect(User::where('email', 'link@internara.test')->count())
            ->toBe(1)
            ->and(User::where('email', 'link@internara.test')->first()->name)
            ->toBe('New Name');
    });
});
