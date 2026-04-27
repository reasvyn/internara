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
    Gate::define('performStep', fn() => true);
    Gate::define('saveSettings', fn() => true);
    Gate::define('finalize', fn() => true);

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
    test('it completes setup_step_welcome successfully', function () {
        // 1. Welcome -> Environment
        $this->get(route('setup.welcome', ['token' => 'test-token']));

        Livewire::test(SetupWelcome::class)
            ->call('nextStep')
            ->assertRedirect(route('setup.environment'));

        expect(
            app(SettingService::class)->getValue('setup_step_welcome', skipCache: true),
        )->toBeTrue();
    });

    test('it completes setup_step_environment successfully', function () {
        $settings = app(SettingService::class);

        // 2. Environment -> School
        $settings->setValue('setup_step_welcome', true);
        $this->get(route('setup.environment', ['token' => 'test-token']));

        Livewire::test(EnvironmentSetup::class)
            ->call('nextStep')
            ->assertRedirect(route('setup.school'));

        expect($settings->getValue('setup_step_environment', skipCache: true))->toBeTrue();
    });

    test('it completes setup_step_school successfully', function () {
        $settings = app(SettingService::class);
        $settings->setValue('setup_step_environment', true);

        // 3. School -> Account
        School::factory()->create();
        $this->get(route('setup.school', ['token' => 'test-token']));

        Livewire::test(SchoolSetup::class)
            ->call('nextStep')
            ->assertRedirect(route('setup.account'));

        expect($settings->getValue('setup_step_school', skipCache: true))->toBeTrue();
    });

    test('it completes setup_step_account successfully', function () {
        $settings = app(SettingService::class);
        $settings->setValue('setup_step_school', true);

        // 4. Account -> Department
        $superAdmin = app(SuperAdminService::class)->create([
            'name' => 'Admin Test',
            'username' => 'admin.test',
            'email' => 'admin@internara.test',
            'password' => 'password',
        ]);
        $this->get(route('setup.account', ['token' => 'test-token']));

        Livewire::test(AccountSetup::class)
            ->dispatch('super_admin_registered')
            ->assertRedirect(route('setup.department'));

        expect($settings->getValue('setup_step_account', skipCache: true))->toBeTrue();
    });

    test('it completes setup_step_department successfully', function () {
        $settings = app(SettingService::class);
        $settings->setValue('setup_step_account', true);

        // 5. Department -> Internship
        Department::factory()->create();
        $this->get(route('setup.department', ['token' => 'test-token']));

        Livewire::test(DepartmentSetup::class)
            ->call('nextStep')
            ->assertRedirect(route('setup.internship'));

        expect($settings->getValue('setup_step_department', skipCache: true))->toBeTrue();
    });

    test('it completes setup_step_internship successfully', function () {
        $settings = app(SettingService::class);
        $settings->setValue('setup_step_department', true);

        // 6. Internship -> System
        Internship::factory()->create();
        $this->get(route('setup.internship', ['token' => 'test-token']));
        Livewire::test(InternshipSetup::class)
            ->call('nextStep')
            ->assertRedirect(route('setup.system'));

        expect($settings->getValue('setup_step_internship', skipCache: true))->toBeTrue();
    });

    test('it completes setup_step_system successfully', function () {
        $settings = app(SettingService::class);
        $settings->setValue('setup_step_internship', true);

        // 7. System -> Complete
        $this->get(route('setup.system', ['token' => 'test-token']));

        Livewire::test(SystemSetup::class)
            ->call('nextStep')
            ->assertRedirect(route('setup.complete'));

        expect($settings->getValue('setup_step_system', skipCache: true))->toBeTrue();
    });

    test('it completes setup_step_complete successfully', function () {
        $settings = app(SettingService::class);
        $settings->setValue('setup_step_system', true);

        // 8. Complete -> Dashboard (Login)
        if (!School::exists()) {
            School::factory()->create();
        }
        $this->get(route('setup.complete', ['token' => 'test-token']));

        Livewire::test(SetupComplete::class)
            ->set('data_verified', true)
            ->set('security_aware', true)
            ->set('legal_agreed', true)
            ->call('nextStep')
            ->assertRedirect(route('login'));

        expect($settings->getValue('setup_step_complete', skipCache: true))->toBeTrue();
    });
});

describe('Setup Wizard Protection', function () {
    test('it returns 404 if app is installed', function () {
        app(SettingService::class)->setValue('app_installed', true);
        $this->get('/setup/welcome')->assertNotFound();
    });

    test('it returns 403 if request has invalid access token', function () {
        $this->get('/setup/welcome', ['token' => 'wrong-token'])->assertForbidden();
    });
});
