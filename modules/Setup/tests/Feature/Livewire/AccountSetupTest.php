<?php

declare(strict_types=1);

namespace Modules\Setup\Tests\Feature\Livewire;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Modules\Admin\Services\Contracts\SuperAdminService;
use Modules\Permission\Database\Seeders\PermissionSeeder;
use Modules\Permission\Database\Seeders\RoleSeeder;
use Modules\Setting\Services\Contracts\SettingService;
use Modules\Setup\Livewire\AccountSetup;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    App::setLocale('en');
    $this->seed(PermissionSeeder::class);
    $this->seed(RoleSeeder::class);

    // Authorization for setup (Middleware & Gates)
    app(SettingService::class)->setValue('app_installed', false);
    app(SettingService::class)->setValue('setup_token', 'test-token');
    Gate::define('performStep', fn() => true);
});

describe('AccountSetup Component', function () {
    test('it renders correctly and contains the registration slot', function () {
        app(SettingService::class)->setValue('setup_step_school', true);

        $this->get(route('setup.account', ['token' => 'test-token']));

        Livewire::test(AccountSetup::class)
            ->assertStatus(200)
            ->assertSee(__('setup::wizard.account.headline'));
    });

    test('it proceeds to department setup step on registration event', function () {
        app(SettingService::class)->setValue('setup_step_school', true);

        // Required record 'super-admin' must exist for nextStep() to succeed
        $superAdmin = app(SuperAdminService::class)->create([
            'name' => 'Admin',
            'username' => 'admin',
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);
        $superAdmin->assignRole('super-admin');

        $this->get(route('setup.account', ['token' => 'test-token']));

        Livewire::test(AccountSetup::class)
            ->dispatch('super_admin_registered')
            ->assertRedirect(route('setup.department'));
    });

    test('it enforces setup sequence access control by redirecting', function () {
        // Prev step 'school' is NOT completed, should redirect to it
        app(SettingService::class)->setValue('setup_step_school', false);

        $this->get(route('setup.account', ['token' => 'test-token']));

        Livewire::test(AccountSetup::class)->assertRedirect(route('setup.school'));
    });

    test('it adheres to [SYRS-NF-401] with responsive layout', function () {
        app(SettingService::class)->setValue('setup_step_school', true);

        $this->get(route('setup.account', ['token' => 'test-token']));

        Livewire::test(AccountSetup::class)->assertSeeHtml('text-4xl');
    });
});
