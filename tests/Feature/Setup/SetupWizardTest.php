<?php

declare(strict_types=1);

namespace Tests\Feature\Setup;

use App\Enums\Auth\Role as RoleEnum;
use App\Livewire\Setup\Components\WelcomeStep;
use App\Livewire\Setup\SetupWizard;
use App\Services\Setup\SetupService;
use Illuminate\Support\Facades\File;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Livewire::component('setup.components.welcome-step', WelcomeStep::class);

    foreach (RoleEnum::cases() as $role) {
        Role::firstOrCreate([
            'name' => $role->value,
            'guard_name' => 'web',
        ]);
    }

    $service = new SetupService;
    if ($service->isInstalled()) {
        File::delete(storage_path('app/.installed'));
    }
    $service->clearSession();

    // Set locale to English for consistent test behavior
    app()->setLocale('en');
});

afterEach(function () {
    $service = new SetupService;
    if ($service->isInstalled()) {
        File::delete(storage_path('app/.installed'));
    }
    $service->clearSession();
});

test('setup wizard renders welcome step', function () {
    $service = new SetupService;
    $token = $service->generateToken();
    $service->authorizeSession($token);

    Livewire::withQueryParams(['setup_token' => $token])
        ->test(SetupWizard::class)
        ->assertSet('currentStep', 1)
        ->assertSee('Welcome to Internara');
});

test('setup wizard redirects if already installed', function () {
    $service = new SetupService;
    $service->finalize();

    Livewire::test(SetupWizard::class)->assertRedirect(route('login'));
});

test('setup wizard advances from welcome step when audit passes', function () {
    $service = new SetupService;
    $token = $service->generateToken();
    $service->authorizeSession($token);

    Livewire::withQueryParams(['setup_token' => $token])
        ->test(SetupWizard::class)
        ->set('auditPassed', true)
        ->call('nextStep')
        ->assertSet('currentStep', 2);
});

test('setup wizard validates school data', function () {
    $service = new SetupService;
    $token = $service->generateToken();
    $service->authorizeSession($token);
    $service->completeStep('welcome');
    $service->setCurrentStep(2);

    Livewire::withQueryParams(['setup_token' => $token])
        ->test(SetupWizard::class)
        ->set('currentStep', 2)
        ->set('schoolName', '')
        ->set('schoolCode', '')
        ->set('schoolAddress', '')
        ->set('schoolEmail', 'invalid-email')
        ->call('nextStep')
        ->assertHasErrors(['schoolName', 'schoolCode', 'schoolAddress', 'schoolEmail']);
});

test('setup wizard validates admin credentials', function () {
    $service = new SetupService;
    $token = $service->generateToken();
    $service->authorizeSession($token);
    $service->completeStep('welcome');
    $service->completeStep('school');
    $service->setCurrentStep(3);

    Livewire::withQueryParams(['setup_token' => $token])
        ->test(SetupWizard::class)
        ->set('currentStep', 3)
        ->set('adminName', '')
        ->set('adminEmail', 'invalid')
        ->set('adminPassword', '')
        ->set('adminPassword_confirmation', '')
        ->call('nextStep')
        ->assertHasErrors(['adminName', 'adminEmail', 'adminPassword']);
});

test('setup wizard requires finalization checkboxes', function () {
    $service = new SetupService;
    $token = $service->generateToken();
    $service->authorizeSession($token);
    $service->completeStep('welcome');
    $service->completeStep('school');
    $service->completeStep('account');
    $service->completeStep('department');
    $service->completeStep('internship');
    $service->setCurrentStep(5);

    Livewire::withQueryParams(['setup_token' => $token])
        ->test(SetupWizard::class)
        ->set('currentStep', 5)
        ->set('internshipName', 'Test Internship')
        ->set('startDate', '2026-06-01')
        ->set('endDate', '2026-09-01')
        ->set('dataVerified', false)
        ->set('securityAware', false)
        ->call('finish')
        ->assertHasErrors(['dataVerified', 'securityAware']);
});

test('setup wizard completes and creates lock file', function () {
    $service = new SetupService;
    $token = $service->generateToken();
    $service->authorizeSession($token);
    $service->completeStep('welcome');
    $service->completeStep('school');
    $service->completeStep('account');
    $service->completeStep('department');
    $service->completeStep('internship');
    $service->setCurrentStep(5);

    Livewire::withQueryParams(['setup_token' => $token])
        ->test(SetupWizard::class)
        ->set('currentStep', 5)
        ->set('schoolName', 'Test School')
        ->set('schoolCode', 'TEST001')
        ->set('schoolAddress', '123 Test Street')
        ->set('adminName', 'Setup Admin')
        ->set('adminEmail', 'admin@testsetup.com')
        ->set('adminUsername', 'u12345678')
        ->set('adminPassword', 'password123')
        ->set('adminPassword_confirmation', 'password123')
        ->set('departmentName', 'Test Department')
        ->set('internshipName', 'Test Internship')
        ->set('startDate', '2026-06-01')
        ->set('endDate', '2026-09-01')
        ->set('dataVerified', true)
        ->set('securityAware', true)
        ->call('finish')
        ->assertSet('currentStep', 7);

    expect($service->isInstalled())
        ->toBeTrue()
        ->and(File::exists(storage_path('app/.installed')))
        ->toBeTrue();
});
