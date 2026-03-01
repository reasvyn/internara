<?php

declare(strict_types=1);

namespace Modules\Setup\Tests\Feature\Livewire;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Modules\Core\Academic\Support\AcademicYear;
use Modules\Department\Services\Contracts\DepartmentService;
use Modules\Internship\Services\Contracts\InternshipService;
use Modules\Permission\Database\Seeders\PermissionSeeder;
use Modules\Permission\Database\Seeders\RoleSeeder;
use Modules\School\Services\Contracts\SchoolService;
use Modules\Setting\Services\Contracts\SettingService;
use Modules\Setup\Livewire\AccountSetup;
use Modules\Setup\Livewire\DepartmentSetup;
use Modules\Setup\Livewire\EnvironmentSetup;
use Modules\Setup\Livewire\InternshipSetup;
use Modules\Setup\Livewire\SchoolSetup;
use Modules\Setup\Livewire\SetupComplete;
use Modules\Setup\Livewire\SetupWelcome;
use Modules\Setup\Livewire\SystemSetup;
use Modules\User\Services\Contracts\SuperAdminService;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
    $this->seed(RoleSeeder::class);

    app(SettingService::class)->setValue('app_installed', false);
    session(['setup_authorized' => true]);
});

describe('Setup Wizard Transitions', function () {
    test('it completes all steps successfully', function () {
        $settings = app(SettingService::class);
        $currentYear = AcademicYear::current();

        // 1. Welcome
        Livewire::test(SetupWelcome::class)
            ->call('nextStep')
            ->assertRedirect(route('setup.environment'));
        $settings->setValue('setup_step_welcome', true);

        // 2. Environment
        Livewire::test(EnvironmentSetup::class)
            ->call('nextStep')
            ->assertRedirect(route('setup.school'));
        $settings->setValue('setup_step_environment', true);

        // 3. School
        app(SchoolService::class)->factory()->create();
        Livewire::test(SchoolSetup::class)
            ->set('turnstile', 'mock-token')
            ->dispatch('school_saved')
            ->assertRedirect(route('setup.account'));
        $settings->setValue('setup_step_school', true);

        // 4. Account
        $superAdmin = app(SuperAdminService::class)->factory()->create();
        $superAdmin->assignRole('super-admin');

        // Final sanity check for role
        expect($superAdmin->hasRole('super-admin'))
            ->toBeTrue()
            ->and(app(SuperAdminService::class)->exists())
            ->toBeTrue();

        Livewire::test(AccountSetup::class)
            ->set('turnstile', 'mock-token')
            ->dispatch('super_admin_registered')
            ->assertRedirect(route('setup.department'));
        $settings->setValue('setup_step_account', true);

        // 5. Department
        app(DepartmentService::class)->factory()->create();
        Livewire::test(DepartmentSetup::class)
            ->call('nextStep')
            ->assertRedirect(route('setup.internship'));
        $settings->setValue('setup_step_department', true);

        // 6. Internship
        app(InternshipService::class)
            ->factory()
            ->create(['academic_year' => $currentYear]);
        Livewire::test(InternshipSetup::class)
            ->call('nextStep')
            ->assertRedirect(route('setup.system'));
        $settings->setValue('setup_step_internship', true);

        // 7. System
        Livewire::test(SystemSetup::class)
            ->set('turnstile', 'mock-token')
            ->set('mail_host', 'localhost')
            ->set('mail_port', '1025')
            ->set('mail_from_address', 'noreply@internara.id')
            ->set('mail_from_name', 'Internara')
            ->call('save')
            ->assertRedirect(route('setup.complete'));
        $settings->setValue('setup_step_system', true);

        // 8. Complete
        Livewire::test(SetupComplete::class)->call('nextStep')->assertRedirect(route('login'));

        expect($settings->getValue('app_installed', false, true))->toBeTrue();
    });
});
