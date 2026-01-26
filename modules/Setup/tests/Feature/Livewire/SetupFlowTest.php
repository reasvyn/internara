<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Department\Services\Contracts\DepartmentService;
use Modules\Internship\Services\Contracts\InternshipService;
use Modules\Permission\Database\Seeders\PermissionSeeder;
use Modules\Permission\Database\Seeders\RoleSeeder;
use Modules\School\Services\Contracts\SchoolService;
use Modules\Setting\Services\Contracts\SettingService;
use Modules\Setup\Livewire\AccountSetup;
use Modules\Setup\Livewire\DepartmentSetup;
use Modules\Setup\Livewire\InternshipSetup;
use Modules\Setup\Livewire\SchoolSetup;
use Modules\Setup\Livewire\SetupComplete;
use Modules\Setup\Livewire\SetupWelcome;
use Modules\Setup\Livewire\SystemSetup;
use Modules\User\Services\Contracts\SuperAdminService;

uses(RefreshDatabase::class);

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

    // 0. Verify unauthorized access (Invalid token)
    $this->get(route('setup.welcome', ['token' => 'invalid-token']))->assertStatus(403);

    // 1. Welcome Step (Authorized)
    Livewire::withQueryParams(['token' => $token])
        ->test(SetupWelcome::class)
        ->assertStatus(200)
        ->assertSee('Sambut Program Magang yang Bermakna')
        ->call('nextStep')
        ->assertRedirect(route('setup.account'));

    expect(session('setup:welcome'))->toBeTrue();

    // 2. Account Setup
    app(SuperAdminService::class)->factory()->create()->assignRole('super-admin');

    Livewire::test(AccountSetup::class)
        ->assertStatus(200)
        ->dispatch('super-admin-registered')
        ->assertRedirect(route('setup.school'));

    expect(session('setup:account'))->toBeTrue();

    // 3. School Setup
    $school = app(SchoolService::class)->factory()->create();

    Livewire::test(SchoolSetup::class)
        ->assertStatus(200)
        ->dispatch('school_saved')
        ->assertRedirect(route('setup.department'));

    expect(session('setup:school'))->toBeTrue();

    // 4. Department Setup
    app(DepartmentService::class)->factory()->create();

    Livewire::test(DepartmentSetup::class)
        ->assertStatus(200)
        ->call('nextStep')
        ->assertRedirect(route('setup.internship'));

    expect(session('setup:department'))->toBeTrue();

    // 5. Internship Setup
    app(InternshipService::class)->factory()->create();

    Livewire::test(InternshipSetup::class)
        ->assertStatus(200)
        ->call('nextStep')
        ->assertRedirect(route('setup.system'));

    expect(session('setup:internship'))->toBeTrue();

    // 6. System Setup
    Livewire::test(SystemSetup::class)
        ->set('mail_host', 'localhost')
        ->set('mail_port', '1025')
        ->set('mail_from_address', 'noreply@internara.id')
        ->set('mail_from_name', 'Internara')
        ->call('save')
        ->assertRedirect(route('setup.complete'));

    expect(session('setup:system'))->toBeTrue();

    // 7. Complete Step
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

        $this->get(route('setup.welcome'))->assertStatus(302)->assertRedirect(route('login'));
    },
);
