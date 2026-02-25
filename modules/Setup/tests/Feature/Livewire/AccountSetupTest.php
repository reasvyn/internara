<?php

declare(strict_types=1);

namespace Modules\Setup\Tests\Feature\Livewire;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Modules\Permission\Database\Seeders\PermissionSeeder;
use Modules\Permission\Database\Seeders\RoleSeeder;
use Modules\Setting\Services\Contracts\SettingService;
use Modules\Setup\Livewire\AccountSetup;
use Modules\User\Services\Contracts\SuperAdminService;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
    $this->seed(RoleSeeder::class);
});

describe('AccountSetup Component', function () {
    test('it renders correctly and contains the registration slot', function () {
        app(SettingService::class)->setValue('setup_step_school', true);

        Livewire::test(AccountSetup::class)
            ->assertStatus(200)
            ->assertSee(__('setup::wizard.account.headline'));
    });

    test('it proceeds to department setup step on registration event', function () {
        app(SettingService::class)->setValue('setup_step_school', true);

        // Required record 'super-admin' must exist for nextStep() to succeed
        app(SuperAdminService::class)->factory()->create()->assignRole('super-admin');

        Livewire::test(AccountSetup::class)
            ->dispatch('super_admin_registered')
            ->assertRedirect(route('setup.department'));
    });

    test('it enforces setup sequence access control by redirecting', function () {
        // Prev step 'school' is NOT completed, should redirect to it
        Livewire::test(AccountSetup::class)->assertRedirect(route('setup.school'));
    });

    test('it adheres to [SYRS-NF-401] with slot-based registration UI', function () {
        app(SettingService::class)->setValue('setup_step_school', true);

        $viewPath = module_path('Setup', 'resources/views/livewire/account-setup.blade.php');
        $template = file_get_contents($viewPath);

        // Verify slot-based UI integration and responsive typography
        expect($template)
            ->toContain('x-setup::layouts.setup-wizard')
            ->toContain("@slotRender('register.super-admin')")
            ->toContain('text-4xl');
    });
});
