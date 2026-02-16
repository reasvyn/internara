<?php

declare(strict_types=1);

use Livewire\Livewire;
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

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
    $this->seed(RoleSeeder::class);
});

test('it can navigate through the entire setup flow with token authorization', function () {
    $settingService = app(SettingService::class);
    $settingService->setValue('app_installed', false);

    // Generate and store setup token
    $token = \Illuminate\Support\Str::random(32);
    $settingService->setValue('setup_token', $token);

    // 0. Verify unauthorized access (No token)
    $this->get(route('setup.welcome'))->assertStatus(403);

    // 1. Welcome Step (Authorized)
    Livewire::withQueryParams(['token' => $token])
        ->test(SetupWelcome::class)
        ->assertStatus(200)
        ->assertSee(__('setup::wizard.welcome.headline'))
        ->call('nextStep')
        ->assertRedirect(route('setup.environment'));

    expect($settingService->getValue('setup_step_welcome'))->toBeTrue();

    // 2. Environment Step
    Livewire::test(EnvironmentSetup::class)
        ->assertStatus(200)
        ->assertSee(__('setup::wizard.environment.title'))
        ->call('nextStep')
        ->assertRedirect(route('setup.school'));

    expect($settingService->getValue('setup_step_environment'))->toBeTrue();

    // 3. School Setup
    $school = app(SchoolService::class)->factory()->create();

    Livewire::test(SchoolSetup::class)
        ->assertStatus(200)
        ->dispatch('school_saved')
        ->assertRedirect(route('setup.account'));

    expect($settingService->getValue('setup_step_school'))->toBeTrue();

    // 4. Account Setup
    app(SuperAdminService::class)->factory()->create()->assignRole('super-admin');

    Livewire::test(AccountSetup::class)
        ->assertStatus(200)
        ->dispatch('super-admin-registered')
        ->assertRedirect(route('setup.department'));

    expect($settingService->getValue('setup_step_account'))->toBeTrue();

    // 5. Department Setup
    app(DepartmentService::class)->factory()->create();

    Livewire::test(DepartmentSetup::class)
        ->assertStatus(200)
        ->call('nextStep')
        ->assertRedirect(route('setup.internship'));

    expect($settingService->getValue('setup_step_department'))->toBeTrue();

    // 6. Internship Setup
    app(InternshipService::class)->factory()->create();

    Livewire::test(InternshipSetup::class)
        ->assertStatus(200)
        ->call('nextStep')
        ->assertRedirect(route('setup.system'));

    expect($settingService->getValue('setup_step_internship'))->toBeTrue();

    // 7. System Setup
    Livewire::test(SystemSetup::class)
        ->set('mail_host', 'localhost')
        ->set('mail_port', '1025')
        ->set('mail_from_address', 'noreply@internara.id')
        ->set('mail_from_name', 'Internara')
        ->call('save')
        ->assertRedirect(route('setup.complete'));

    expect($settingService->getValue('setup_step_system'))->toBeTrue();

    // 8. Complete Step
    Livewire::test(SetupComplete::class)
        ->assertStatus(200)
        ->call('nextStep')
        ->assertRedirect(route('login'));

    // Verify final state and token purge
    expect($settingService->getValue('app_installed'))
        ->toBeTrue()
        ->and($settingService->getValue('brand_name'))
        ->toBe($school->name)
        ->and($settingService->getValue('setup_token'))
        ->toBeNull();
});

test(
    'it protects setup routes once application is fully installed and superadmin exists',
    function () {
        app(SettingService::class)->setValue('app_installed', true);
        app(SuperAdminService::class)->factory()->create()->assignRole('super-admin');

        $this->get(route('setup.welcome'))->assertStatus(404);
    },
);
