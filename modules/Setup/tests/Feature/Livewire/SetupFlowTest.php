<?php

declare(strict_types=1);

namespace Modules\Setup\Tests\Feature\Livewire;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Modules\Admin\Services\Contracts\SuperAdminService;
use Modules\Department\Models\Department;
use Modules\Internship\Models\Internship;
use Modules\Permission\Database\Seeders\PermissionSeeder;
use Modules\Permission\Database\Seeders\RoleSeeder;
use Modules\School\Models\School;
use Modules\Setting\Services\Contracts\SettingService;
use Modules\Setup\Livewire\AccountSetup;
use Modules\Setup\Livewire\DepartmentSetup;
use Modules\Setup\Livewire\EnvironmentSetup;
use Modules\Setup\Livewire\InternshipSetup;
use Modules\Setup\Livewire\SchoolSetup;
use Modules\Setup\Livewire\SetupComplete;
use Modules\Setup\Livewire\SetupWelcome;
use Modules\Setup\Livewire\SystemSetup;
use Modules\Setup\Services\Contracts\SystemAuditor;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    App::setLocale('en');
    $this->seed(PermissionSeeder::class);
    $this->seed(RoleSeeder::class);
    
    // Authorization for setup (Middleware & Gates)
    app(SettingService::class)->setValue('app_installed', false);
    app(SettingService::class)->setValue('setup_token', 'test-token');
    Gate::define('performStep', fn () => true);
    Gate::define('saveSettings', fn () => true);
    Gate::define('finalize', fn () => true);

    // Mock environment auditor to always be ready
    $mock = $this->mock(SystemAuditor::class);
    $mock->shouldReceive('passes')->andReturn(true);
    $mock->shouldReceive('audit')->andReturn([
        'requirements' => ['php_version' => true],
        'permissions' => ['storage_directory' => true],
        'database' => ['connection' => true],
    ]);
});

describe('Setup Wizard Transitions', function () {
    test('it completes all steps successfully', function () {
        $settings = app(SettingService::class);

        // 1. Welcome -> Environment
        $this->get(route('setup.welcome', ['token' => 'test-token']));
        Livewire::test(SetupWelcome::class)
            ->call('nextStep')
            ->assertRedirect(route('setup.environment'));
        $settings->setValue('setup_step_welcome', true);

        // 2. Environment -> School
        $this->get(route('setup.environment', ['token' => 'test-token']));
        Livewire::test(EnvironmentSetup::class)
            ->call('nextStep')
            ->assertRedirect(route('setup.school'));
        $settings->setValue('setup_step_environment', true);

        // 3. School -> Account
        School::factory()->create();
        $this->get(route('setup.school', ['token' => 'test-token']));
        Livewire::test(SchoolSetup::class)
            ->call('nextStep')
            ->assertRedirect(route('setup.account'));
        $settings->setValue('setup_step_school', true);

        // 4. Account -> Department
        $superAdmin = app(SuperAdminService::class)->create([
            'name' => 'Admin',
            'username' => 'admin',
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);
        $this->get(route('setup.account', ['token' => 'test-token']));
        Livewire::test(AccountSetup::class)
            ->dispatch('super_admin_registered')
            ->assertRedirect(route('setup.department'));
        $settings->setValue('setup_step_account', true);

        // 5. Department -> Internship
        Department::factory()->create();
        $this->get(route('setup.department', ['token' => 'test-token']));
        Livewire::test(DepartmentSetup::class)
            ->call('nextStep')
            ->assertRedirect(route('setup.internship'));
        $settings->setValue('setup_step_department', true);

        // 6. Internship -> System
        Internship::factory()->create();
        $this->get(route('setup.internship', ['token' => 'test-token']));
        Livewire::test(InternshipSetup::class)
            ->call('nextStep')
            ->assertRedirect(route('setup.system'));
        $settings->setValue('setup_step_internship', true);

        // 7. System -> Complete
        $this->get(route('setup.system', ['token' => 'test-token']));
        Livewire::test(SystemSetup::class)
            ->call('nextStep')
            ->assertRedirect(route('setup.complete'));
        $settings->setValue('setup_step_system', true);

        // 8. Complete -> Dashboard (Login)
        if (!School::exists()) {
            School::factory()->create();
        }
        $this->get(route('setup.complete', ['token' => 'test-token']));
        Livewire::test(SetupComplete::class)
            ->call('nextStep')
            ->assertRedirect(route('login'));
    });
});
