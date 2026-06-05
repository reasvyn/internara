<?php

declare(strict_types=1);

namespace Tests\Feature\SysAdmin\Setup;

use App\SysAdmin\Setup\Livewire\SetupWizard;
use App\SysAdmin\Setup\Models\Setup;
use App\User\Enums\Role as RoleEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    foreach (RoleEnum::cases() as $role) {
        Role::firstOrCreate(['name' => $role->value]);
    }

    config()->set('setup.defaults.admin_name', 'Administrator');
    config()->set('setup.defaults.admin_username', 'superadmin');

    Setup::query()->delete();
    $this->setup = Setup::create([
        'is_installed' => false,
        'completed_steps' => [],
    ]);
});

test('wizard config defines correct step order: welcome > account > school > department > internship > finalize > complete', function () {
    $stepKeys = config('setup.wizard.step_keys');

    expect($stepKeys)->toBe([
        'welcome',
        'account',
        'school',
        'department',
        'internship',
        'finalize',
        'complete',
    ]);
});

test('finalize steps config includes account first, then school and department', function () {
    $finalizeSteps = config('setup.wizard.finalize_steps');

    expect($finalizeSteps)->toBe(['account', 'school', 'department']);
});

test('wizard mounts at step 1 with admin name and username from config', function () {
    Livewire::test(SetupWizard::class)
        ->assertSet('currentStep', 1)
        ->assertSet('adminForm.name', 'Administrator')
        ->assertSet('adminForm.username', 'superadmin');
});

test('step 1 blocks progression when audit has not passed', function () {
    Livewire::test(SetupWizard::class)
        ->set('auditPassed', false)
        ->call('nextStep')
        ->assertSet('currentStep', 1);
});

test('step 2 validates admin email and password', function () {
    Livewire::test(SetupWizard::class)
        ->call('nextStep') // pass audit at step 1
        ->assertSet('currentStep', 2)
        ->set('adminForm.email', 'invalid-email')
        ->set('adminForm.password', 'short')
        ->call('nextStep')
        ->assertHasErrors(['adminForm.email', 'adminForm.password'])
        ->assertSet('currentStep', 2);
});

test('step 2 requires password confirmation to match', function () {
    Livewire::test(SetupWizard::class)
        ->call('nextStep')
        ->assertSet('currentStep', 2)
        ->set('adminForm.email', 'admin@school.test')
        ->set('adminForm.password', 'SecurePass123!')
        ->set('adminForm.password_confirmation', 'Different456!')
        ->call('nextStep')
        ->assertHasErrors(['adminForm.password'])
        ->assertSet('currentStep', 2);
});

test('step 2 requires password complexity (mixed case + numbers)', function () {
    Livewire::test(SetupWizard::class)
        ->call('nextStep')
        ->assertSet('currentStep', 2)
        ->set('adminForm.email', 'admin@school.test')
        ->set('adminForm.password', 'simple')
        ->set('adminForm.password_confirmation', 'simple')
        ->call('nextStep')
        ->assertHasErrors(['adminForm.password'])
        ->assertSet('currentStep', 2);
});

test('step 2 proceeds to step 3 with valid admin data', function () {
    Livewire::test(SetupWizard::class)
        ->call('nextStep')
        ->assertSet('currentStep', 2)
        ->set('adminForm.email', 'admin@school.test')
        ->set('adminForm.password', 'SecurePass123!')
        ->set('adminForm.password_confirmation', 'SecurePass123!')
        ->call('nextStep')
        ->assertHasNoErrors()
        ->assertSet('currentStep', 3);
});

test('step 3 validates school form fields', function () {
    Livewire::test(SetupWizard::class)
        ->call('nextStep') // welcome → step 2
        ->set('adminForm.email', 'admin@school.test')
        ->set('adminForm.password', 'SecurePass123!')
        ->set('adminForm.password_confirmation', 'SecurePass123!')
        ->call('nextStep') // step 2 → step 3
        ->assertSet('currentStep', 3)
        ->set('schoolForm.name', '')
        ->call('nextStep')
        ->assertHasErrors(['schoolForm.name'])
        ->assertSet('currentStep', 3);
});

test('step 3 rejects invalid school email format', function () {
    Livewire::test(SetupWizard::class)
        ->call('nextStep')
        ->set('adminForm.email', 'admin@school.test')
        ->set('adminForm.password', 'SecurePass123!')
        ->set('adminForm.password_confirmation', 'SecurePass123!')
        ->call('nextStep')
        ->assertSet('currentStep', 3)
        ->set('schoolForm.name', 'Test School')
        ->set('schoolForm.institutional_code', 'SCH001')
        ->set('schoolForm.email', 'not-an-email')
        ->call('nextStep')
        ->assertHasErrors(['schoolForm.email'])
        ->assertSet('currentStep', 3);
});

test('step 3 proceeds to step 4 with valid school data', function () {
    Livewire::test(SetupWizard::class)
        ->call('nextStep')
        ->set('adminForm.email', 'admin@school.test')
        ->set('adminForm.password', 'SecurePass123!')
        ->set('adminForm.password_confirmation', 'SecurePass123!')
        ->call('nextStep')
        ->assertSet('currentStep', 3)
        ->set('schoolForm.name', 'Test School')
        ->set('schoolForm.institutional_code', 'SCH001')
        ->set('schoolForm.email', 'school@test.com')
        ->call('nextStep')
        ->assertHasNoErrors()
        ->assertSet('currentStep', 4);
});

test('step 4 validates department name', function () {
    Livewire::test(SetupWizard::class)
        ->call('nextStep')
        ->set('adminForm.email', 'admin@school.test')
        ->set('adminForm.password', 'SecurePass123!')
        ->set('adminForm.password_confirmation', 'SecurePass123!')
        ->call('nextStep')
        ->set('schoolForm.name', 'Test School')
        ->set('schoolForm.institutional_code', 'SCH001')
        ->set('schoolForm.email', 'school@test.com')
        ->call('nextStep')
        ->assertSet('currentStep', 4)
        ->set('departmentForm.name', '')
        ->call('nextStep')
        ->assertHasErrors(['departmentForm.name'])
        ->assertSet('currentStep', 4);
});

test('step 4 proceeds to step 5 with valid department name', function () {
    Livewire::test(SetupWizard::class)
        ->call('nextStep')
        ->set('adminForm.email', 'admin@school.test')
        ->set('adminForm.password', 'SecurePass123!')
        ->set('adminForm.password_confirmation', 'SecurePass123!')
        ->call('nextStep')
        ->set('schoolForm.name', 'Test School')
        ->set('schoolForm.institutional_code', 'SCH001')
        ->set('schoolForm.email', 'school@test.com')
        ->call('nextStep')
        ->set('departmentForm.name', 'Computer Science')
        ->call('nextStep')
        ->assertHasNoErrors()
        ->assertSet('currentStep', 5);
});

test('step 5 skips internship validation when empty (optional)', function () {
    Livewire::test(SetupWizard::class)
        ->call('nextStep')
        ->set('adminForm.email', 'admin@school.test')
        ->set('adminForm.password', 'SecurePass123!')
        ->set('adminForm.password_confirmation', 'SecurePass123!')
        ->call('nextStep')
        ->set('schoolForm.name', 'Test School')
        ->set('schoolForm.institutional_code', 'SCH001')
        ->set('schoolForm.email', 'school@test.com')
        ->call('nextStep')
        ->set('departmentForm.name', 'Computer Science')
        ->call('nextStep')
        ->assertSet('currentStep', 5)
        ->call('nextStep')
        ->assertHasNoErrors()
        ->assertSet('currentStep', 6);
});

test('step 5 validates internship when partially filled', function () {
    Livewire::test(SetupWizard::class)
        ->call('nextStep')
        ->set('adminForm.email', 'admin@school.test')
        ->set('adminForm.password', 'SecurePass123!')
        ->set('adminForm.password_confirmation', 'SecurePass123!')
        ->call('nextStep')
        ->set('schoolForm.name', 'Test School')
        ->set('schoolForm.institutional_code', 'SCH001')
        ->set('schoolForm.email', 'school@test.com')
        ->call('nextStep')
        ->set('departmentForm.name', 'Computer Science')
        ->call('nextStep')
        ->assertSet('currentStep', 5)
        ->set('internshipForm.name', 'PKL 2026')
        ->call('nextStep')
        ->assertHasErrors(['internshipForm.start_date', 'internshipForm.end_date'])
        ->assertSet('currentStep', 5);
});

test('prevStep does not go below step 1', function () {
    Livewire::test(SetupWizard::class)
        ->assertSet('currentStep', 1)
        ->call('prevStep')
        ->assertSet('currentStep', 1)
        ->call('prevStep')
        ->assertSet('currentStep', 1);
});

test('admin name and username are immutable from config on the form', function () {
    Livewire::test(SetupWizard::class)
        ->assertSet('adminForm.name', 'Administrator')
        ->assertSet('adminForm.username', 'superadmin');

    // Changing config changes defaults
    config()->set('setup.defaults.admin_name', 'Super Admin');
    config()->set('setup.defaults.admin_username', 'root');

    Livewire::test(SetupWizard::class)
        ->assertSet('adminForm.name', 'Super Admin')
        ->assertSet('adminForm.username', 'root');
});

test('complete step view contains auto-redirect timer script', function () {
    $component = Livewire::test(SetupWizard::class)
        ->set('currentStep', 7);

    $output = $component->html();

    expect($output)->toContain('setTimeout');
    expect($output)->toContain('60000');
    expect($output)->toContain('/login');
});

test('full setup wizard flow with new step order creates all entities', function () {
    Livewire::test(SetupWizard::class)
        // Step 1: Welcome (audit passes by default in testing)
        ->call('nextStep')
        ->assertSet('currentStep', 2)

        // Step 2: Admin
        ->set('adminForm.email', 'admin@school.test')
        ->set('adminForm.password', 'SecurePass123!')
        ->set('adminForm.password_confirmation', 'SecurePass123!')
        ->call('nextStep')
        ->assertSet('currentStep', 3)

        // Step 3: School
        ->set('schoolForm.name', 'Test School')
        ->set('schoolForm.institutional_code', 'SCH001')
        ->set('schoolForm.email', 'school@test.com')
        ->set('schoolForm.address', '123 School St')
        ->set('schoolForm.phone', '0812345678')
        ->set('schoolForm.website', 'https://school.test')
        ->set('schoolForm.principal_name', 'Dr. Principal')
        ->call('nextStep')
        ->assertSet('currentStep', 4)

        // Step 4: Department
        ->set('departmentForm.name', 'Software Engineering')
        ->set('departmentForm.description', 'SE Dept')
        ->call('nextStep')
        ->assertSet('currentStep', 5)

        // Step 5: Internship (optional, skip)
        ->call('nextStep')
        ->assertSet('currentStep', 6)

        // Step 6: Finalize
        ->set('dataVerified', true)
        ->set('securityAware', true)
        ->call('finish')
        ->assertHasNoErrors()
        ->assertSet('currentStep', 7);

    // Verify entities
    $setup = Setup::first();
    expect($setup->is_installed)->toBeTrue();
    expect($setup->school_id)->not->toBeNull();
    expect($setup->department_id)->not->toBeNull();
    expect($setup->recovery_key)->not->toBeNull();
});
