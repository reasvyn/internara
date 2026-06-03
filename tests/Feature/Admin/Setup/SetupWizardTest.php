<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Setup;

use App\Domain\Admin\Aggregates\Setup\Livewire\SetupWizard;
use App\Domain\Admin\Aggregates\Setup\Models\Setup;
use App\Domain\User\Enums\AccountStatus;
use App\Domain\User\Enums\Role as RoleEnum;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Seed Spatie roles so assigning role works
    foreach (RoleEnum::cases() as $role) {
        Role::firstOrCreate(['name' => $role->value]);
    }

    // Set setup config defaults
    config()->set('setup.defaults.admin_name', 'Administrator');
    config()->set('setup.defaults.admin_username', 'superadmin');

    // Create a setup record that is NOT installed
    Setup::query()->delete();
    $this->setup = Setup::create([
        'is_installed' => false,
        'completed_steps' => [],
    ]);
});

afterEach(function () {
    File::delete(storage_path('app/private/.recovery-key'));
});

test('setup wizard redirects to login if system is already installed', function () {
    $this->setup->update(['is_installed' => true]);

    Livewire::test(SetupWizard::class)
        ->assertRedirect(route('login'));
});

test('setup wizard initializes defaults, runs audit and loads step 1 on mount', function () {
    $component = Livewire::test(SetupWizard::class)
        ->assertSet('currentStep', 1)
        ->assertSet('adminForm.name', 'Administrator')
        ->assertSet('adminForm.username', 'superadmin');

    expect($component->get('audit'))->not->toBeEmpty();
});

test('setup wizard navigation works correctly step-by-step', function () {
    Livewire::test(SetupWizard::class)
        ->assertSet('currentStep', 1)
        // Step 1 -> Next (Audit passes by default in testing environment)
        ->call('nextStep')
        ->assertSet('currentStep', 2)

        // Step 2 (School form validation fails if blank)
        ->set('schoolForm.name', '')
        ->call('nextStep')
        ->assertHasErrors(['schoolForm.name'])
        ->assertSet('currentStep', 2)

        // Fill Step 2
        ->set('schoolForm.name', 'Test School')
        ->set('schoolForm.institutional_code', 'SCH123')
        ->set('schoolForm.address', '123 School St')
        ->set('schoolForm.email', 'school@example.com')
        ->set('schoolForm.phone', '0812345678')
        ->set('schoolForm.website', 'https://school.example.com')
        ->set('schoolForm.principal_name', 'Dr. Principal')
        ->call('nextStep')
        ->assertHasNoErrors()
        ->assertSet('currentStep', 3)

        // Step 3 (Department validation fails if blank)
        ->set('departmentForm.name', '')
        ->call('nextStep')
        ->assertHasErrors(['departmentForm.name'])
        ->assertSet('currentStep', 3)

        // Fill Step 3
        ->set('departmentForm.name', 'Computer Science')
        ->set('departmentForm.description', 'CS Dept')
        ->call('nextStep')
        ->assertHasNoErrors()
        ->assertSet('currentStep', 4)

        // Step 4 (Admin validation fails if invalid email/password)
        ->set('adminForm.email', 'invalid-email')
        ->set('adminForm.password', 'short')
        ->call('nextStep')
        ->assertHasErrors(['adminForm.email', 'adminForm.password'])
        ->assertSet('currentStep', 4)

        // Fill Step 4
        ->set('adminForm.email', 'admin@example.com')
        ->set('adminForm.password', 'SecurePassword123!')
        ->set('adminForm.password_confirmation', 'SecurePassword123!')
        ->call('nextStep')
        ->assertHasNoErrors()
        ->assertSet('currentStep', 5)

        // Step 5 (Optional Internship Form - proceed empty)
        ->call('nextStep')
        ->assertSet('currentStep', 6)

        // Test prevStep
        ->call('prevStep')
        ->assertSet('currentStep', 5);
});

test('setup wizard finishes setup successfully, creating school, department, superadmin and recovery key', function () {
    Livewire::test(SetupWizard::class)
        // Step 2: School
        ->set('schoolForm.name', 'Test School')
        ->set('schoolForm.institutional_code', 'SCH123')
        ->set('schoolForm.address', '123 School St')
        ->set('schoolForm.email', 'school@example.com')
        ->set('schoolForm.phone', '0812345678')
        ->set('schoolForm.website', 'https://school.example.com')
        ->set('schoolForm.principal_name', 'Dr. Principal')
        // Step 3: Department
        ->set('departmentForm.name', 'Computer Science')
        ->set('departmentForm.description', 'CS Dept')
        // Step 4: Admin
        ->set('adminForm.email', 'admin@example.com')
        ->set('adminForm.password', 'SecurePassword123!')
        ->set('adminForm.password_confirmation', 'SecurePassword123!')
        // Step 6: Finalize (Accept checkboxes)
        ->set('dataVerified', true)
        ->set('securityAware', true)
        ->call('finish')
        ->assertHasNoErrors()
        ->assertSet('currentStep', 7);

    // Verify system state changes
    $freshSetup = Setup::first();
    expect($freshSetup->is_installed)->toBeTrue();
    expect($freshSetup->school_id)->not->toBeNull();
    expect($freshSetup->department_id)->not->toBeNull();
    expect($freshSetup->recovery_key)->not->toBeNull();

    // Verify Superadmin user was created and set to PROTECTED
    $user = User::where('email', 'admin@example.com')->first();
    expect($user)->not->toBeNull();
    expect($user->name)->toBe('Administrator');
    expect($user->username)->toBe('superadmin');
    expect($user->hasRole('superadmin'))->toBeTrue();
    expect($user->status)->toBe(AccountStatus::PROTECTED->value);
});
