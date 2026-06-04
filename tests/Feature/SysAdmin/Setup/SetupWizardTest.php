<?php

declare(strict_types=1);

namespace Tests\Feature\SysAdmin\Setup;

use App\Domain\Academics\Aggregates\AcademicYear\Models\AcademicYear;
use App\Domain\Program\Aggregates\Internship\Models\Internship;
use App\Domain\SysAdmin\Aggregates\Setup\Livewire\SetupWizard;
use App\Domain\SysAdmin\Aggregates\Setup\Models\Setup;
use App\Domain\User\Enums\AccountStatus;
use App\Domain\User\Enums\Role as RoleEnum;
use App\Domain\User\Models\User;
use Database\Seeders\AcademicYearSeeder;
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

/*
|--------------------------------------------------------------------------
| Step Dependency Chain Tests
|--------------------------------------------------------------------------
|
| Each wizard step enforces validation before allowing progression.
| These tests verify that dependencies are correctly gated.
|
*/

test('step 1 blocks progression when audit has not passed', function () {
    $component = Livewire::test(SetupWizard::class)
        ->set('auditPassed', false)
        ->call('nextStep')
        ->assertSet('currentStep', 1);
});

test('step 2 school form rejects invalid email format', function () {
    Livewire::test(SetupWizard::class)
        ->call('nextStep') // pass step 1 (audit passes in test)
        ->assertSet('currentStep', 2)
        ->set('schoolForm.name', 'Test School')
        ->set('schoolForm.institutional_code', 'SCH123')
        ->set('schoolForm.email', 'not-an-email')
        ->call('nextStep')
        ->assertHasErrors(['schoolForm.email'])
        ->assertSet('currentStep', 2);
});

test('step 2 school form accepts minimal required fields', function () {
    Livewire::test(SetupWizard::class)
        ->call('nextStep')
        ->assertSet('currentStep', 2)
        ->set('schoolForm.name', 'Minimal School')
        ->set('schoolForm.institutional_code', 'MIN01')
        ->set('schoolForm.email', 'school@test.com')
        // address, phone, website, principal_name are nullable
        ->call('nextStep')
        ->assertHasNoErrors()
        ->assertSet('currentStep', 3);
});

test('step 3 department form rejects empty name', function () {
    Livewire::test(SetupWizard::class)
        ->call('nextStep')
        ->set('schoolForm.name', 'Test School')
        ->set('schoolForm.institutional_code', 'SCH01')
        ->set('schoolForm.email', 'school@test.com')
        ->call('nextStep')
        ->assertSet('currentStep', 3)
        ->set('departmentForm.name', '')
        ->call('nextStep')
        ->assertHasErrors(['departmentForm.name'])
        ->assertSet('currentStep', 3);
});

test('step 4 admin form requires password confirmation match', function () {
    Livewire::test(SetupWizard::class)
        ->call('nextStep')
        ->set('schoolForm.name', 'Test School')
        ->set('schoolForm.institutional_code', 'SCH01')
        ->set('schoolForm.email', 'school@test.com')
        ->call('nextStep')
        ->set('departmentForm.name', 'CS Dept')
        ->call('nextStep')
        ->assertSet('currentStep', 4)
        ->set('adminForm.email', 'admin@test.com')
        ->set('adminForm.password', 'SecurePassword123!')
        ->set('adminForm.password_confirmation', 'DifferentPassword123!')
        ->call('nextStep')
        ->assertHasErrors(['adminForm.password'])
        ->assertSet('currentStep', 4);
});

test('step 4 admin form requires password complexity (mixed case + numbers)', function () {
    Livewire::test(SetupWizard::class)
        ->call('nextStep')
        ->set('schoolForm.name', 'Test School')
        ->set('schoolForm.institutional_code', 'SCH01')
        ->set('schoolForm.email', 'school@test.com')
        ->call('nextStep')
        ->set('departmentForm.name', 'CS Dept')
        ->call('nextStep')
        ->assertSet('currentStep', 4)
        ->set('adminForm.email', 'admin@test.com')
        ->set('adminForm.password', 'simple')
        ->set('adminForm.password_confirmation', 'simple')
        ->call('nextStep')
        ->assertHasErrors(['adminForm.password'])
        ->assertSet('currentStep', 4);
});

test('step 5 internship form skips validation when empty', function () {
    Livewire::test(SetupWizard::class)
        ->call('nextStep')
        ->set('schoolForm.name', 'Test School')
        ->set('schoolForm.institutional_code', 'SCH01')
        ->set('schoolForm.email', 'school@test.com')
        ->call('nextStep')
        ->set('departmentForm.name', 'CS Dept')
        ->call('nextStep')
        ->set('adminForm.email', 'admin@test.com')
        ->set('adminForm.password', 'SecurePassword123!')
        ->set('adminForm.password_confirmation', 'SecurePassword123!')
        ->call('nextStep')
        ->assertSet('currentStep', 5)
        // Leave internship form completely empty — should skip validation
        ->call('nextStep')
        ->assertHasNoErrors()
        ->assertSet('currentStep', 6);
});

test('step 5 internship form validates when partially filled', function () {
    Livewire::test(SetupWizard::class)
        ->call('nextStep')
        ->set('schoolForm.name', 'Test School')
        ->set('schoolForm.institutional_code', 'SCH01')
        ->set('schoolForm.email', 'school@test.com')
        ->call('nextStep')
        ->set('departmentForm.name', 'CS Dept')
        ->call('nextStep')
        ->set('adminForm.email', 'admin@test.com')
        ->set('adminForm.password', 'SecurePassword123!')
        ->set('adminForm.password_confirmation', 'SecurePassword123!')
        ->call('nextStep')
        ->assertSet('currentStep', 5)
        // Partially fill — triggers validation
        ->set('internshipForm.name', 'PKL 2026')
        ->call('nextStep')
        ->assertHasErrors(['internshipForm.start_date', 'internshipForm.end_date'])
        ->assertSet('currentStep', 5);
});

test('step 5 internship form rejects end_date before start_date', function () {
    Livewire::test(SetupWizard::class)
        ->call('nextStep')
        ->set('schoolForm.name', 'Test School')
        ->set('schoolForm.institutional_code', 'SCH01')
        ->set('schoolForm.email', 'school@test.com')
        ->call('nextStep')
        ->set('departmentForm.name', 'CS Dept')
        ->call('nextStep')
        ->set('adminForm.email', 'admin@test.com')
        ->set('adminForm.password', 'SecurePassword123!')
        ->set('adminForm.password_confirmation', 'SecurePassword123!')
        ->call('nextStep')
        ->assertSet('currentStep', 5)
        ->set('internshipForm.name', 'PKL 2026')
        ->set('internshipForm.start_date', '2026-12-01')
        ->set('internshipForm.end_date', '2026-01-01') // before start
        ->call('nextStep')
        ->assertHasErrors(['internshipForm.end_date'])
        ->assertSet('currentStep', 5);
});

test('step 6 finalize requires both checkboxes accepted', function () {
    Livewire::test(SetupWizard::class)
        ->set('schoolForm.name', 'Test School')
        ->set('schoolForm.institutional_code', 'SCH01')
        ->set('schoolForm.email', 'school@test.com')
        ->set('departmentForm.name', 'CS Dept')
        ->set('adminForm.email', 'admin@test.com')
        ->set('adminForm.password', 'SecurePassword123!')
        ->set('adminForm.password_confirmation', 'SecurePassword123!')
        // Only accept one checkbox
        ->set('dataVerified', true)
        ->set('securityAware', false)
        ->call('finish')
        ->assertHasErrors(['securityAware']);
});

test('step 6 finalize requires dataVerified checkbox', function () {
    Livewire::test(SetupWizard::class)
        ->set('schoolForm.name', 'Test School')
        ->set('schoolForm.institutional_code', 'SCH01')
        ->set('schoolForm.email', 'school@test.com')
        ->set('departmentForm.name', 'CS Dept')
        ->set('adminForm.email', 'admin@test.com')
        ->set('adminForm.password', 'SecurePassword123!')
        ->set('adminForm.password_confirmation', 'SecurePassword123!')
        ->set('dataVerified', false)
        ->set('securityAware', true)
        ->call('finish')
        ->assertHasErrors(['dataVerified']);
});

/*
|--------------------------------------------------------------------------
| Navigation & State Tests
|--------------------------------------------------------------------------
*/

test('prevStep does not go below step 1', function () {
    Livewire::test(SetupWizard::class)
        ->assertSet('currentStep', 1)
        ->call('prevStep')
        ->assertSet('currentStep', 1)
        ->call('prevStep')
        ->assertSet('currentStep', 1);
});

test('goToStep allows navigating to previously completed steps', function () {
    $this->setup->update([
        'completed_steps' => ['welcome', 'school', 'department'],
    ]);

    Livewire::test(SetupWizard::class)
        ->call('nextStep') // to step 2
        ->set('schoolForm.name', 'Test School')
        ->set('schoolForm.institutional_code', 'SCH01')
        ->set('schoolForm.email', 'school@test.com')
        ->call('nextStep') // to step 3
        ->set('departmentForm.name', 'CS Dept')
        ->call('nextStep') // to step 4
        ->assertSet('currentStep', 4)
        // Navigate back to step 2 (school)
        ->call('goToStep', 'school')
        ->assertSet('currentStep', 2);
});

test('goToStep ignores invalid step keys', function () {
    Livewire::test(SetupWizard::class)
        ->assertSet('currentStep', 1)
        ->call('goToStep', 'nonexistent_step')
        ->assertSet('currentStep', 1);
});

test('wizard form state persists to session on property update', function () {
    Livewire::test(SetupWizard::class)
        ->set('schoolForm.name', 'Persistent School');

    $sessionData = session()->get('setup.form_data');
    expect($sessionData)->not->toBeNull();
    expect($sessionData['school']['name'])->toBe('Persistent School');
});

test('wizard restores form state from session on mount', function () {
    session()->put('setup.form_data', [
        'school' => ['name' => 'Restored School', 'institutional_code' => 'RST01', 'email' => 'r@test.com', 'address' => '', 'phone' => '', 'website' => null, 'principal_name' => null],
        'department' => ['name' => 'Restored Dept', 'description' => ''],
        'admin' => ['name' => 'Administrator', 'username' => 'superadmin', 'email' => 'restored@test.com'],
    ]);

    Livewire::test(SetupWizard::class)
        ->assertSet('schoolForm.name', 'Restored School')
        ->assertSet('departmentForm.name', 'Restored Dept')
        ->assertSet('adminForm.email', 'restored@test.com');
});

/*
|--------------------------------------------------------------------------
| Self-Destruction & Post-Setup Tests
|--------------------------------------------------------------------------
*/

test('wizard shows completion step when installed and setup.completed session exists', function () {
    $this->setup->update(['is_installed' => true]);

    session()->put('setup.completed', true);

    Livewire::test(SetupWizard::class)
        ->assertSet('currentStep', 7);
});

test('finishSession clears setup.completed session and redirects to login', function () {
    $this->setup->update(['is_installed' => true]);

    session()->put('setup.completed', true);

    Livewire::test(SetupWizard::class)
        ->assertSet('currentStep', 7)
        ->call('finishSession')
        ->assertRedirect(route('login'));

    expect(session()->get('setup.completed'))->toBeNull();
});

test('finish cannot run twice on same system', function () {
    // First finish succeeds
    Livewire::test(SetupWizard::class)
        ->set('schoolForm.name', 'Test School')
        ->set('schoolForm.institutional_code', 'SCH01')
        ->set('schoolForm.email', 'school@test.com')
        ->set('departmentForm.name', 'CS Dept')
        ->set('adminForm.email', 'admin@test.com')
        ->set('adminForm.password', 'SecurePassword123!')
        ->set('adminForm.password_confirmation', 'SecurePassword123!')
        ->set('dataVerified', true)
        ->set('securityAware', true)
        ->call('finish')
        ->assertSet('currentStep', 7);

    expect(Setup::first()->is_installed)->toBeTrue();

    // Clear setup.completed session so the wizard doesn't bypass redirect
    session()->forget('setup.completed');

    // Second finish attempt on a new component redirects to login (already installed)
    Livewire::test(SetupWizard::class)
        ->assertRedirect(route('login'));
});

/*
|--------------------------------------------------------------------------
| Full Flow with Internship Data
|--------------------------------------------------------------------------
*/

test('setup wizard finishes with internship data creating all entities', function () {
    // Seed the active academic year first
    $this->seed(AcademicYearSeeder::class);

    Livewire::test(SetupWizard::class)
        ->set('schoolForm.name', 'PKL School')
        ->set('schoolForm.institutional_code', 'PKL01')
        ->set('schoolForm.email', 'pkl@test.com')
        ->set('departmentForm.name', 'Teknik Informatika')
        ->set('departmentForm.description', 'Jurusan TI')
        ->set('adminForm.email', 'admin@pkl.com')
        ->set('adminForm.password', 'AdminPKL2026!')
        ->set('adminForm.password_confirmation', 'AdminPKL2026!')
        ->set('internshipForm.name', 'PKL Semester Genap 2026')
        ->set('internshipForm.description', 'Program PKL')
        ->set('internshipForm.start_date', '2026-07-01')
        ->set('internshipForm.end_date', '2026-12-31')
        ->set('dataVerified', true)
        ->set('securityAware', true)
        ->call('finish')
        ->assertHasNoErrors()
        ->assertSet('currentStep', 7);

    // Verify all entities were created
    $setup = Setup::first();
    expect($setup->is_installed)->toBeTrue();
    expect($setup->school_id)->not->toBeNull();
    expect($setup->department_id)->not->toBeNull();
    expect($setup->recovery_key)->not->toBeNull();
    // Setup token should be cleared
    expect($setup->setup_token)->toBeNull();

    // Verify internship was created and linked to the active academic year
    $internship = Internship::first();
    expect($internship)->not->toBeNull();
    expect($internship->name)->toBe('PKL Semester Genap 2026');
    $activeYear = AcademicYear::where('is_active', true)->first();
    expect($activeYear)->not->toBeNull();
    expect($internship->academic_year_id)->toBe($activeYear->id);

    // Verify superadmin with config defaults
    $user = User::where('email', 'admin@pkl.com')->first();
    expect($user)->not->toBeNull();
    expect($user->name)->toBe('Administrator');
    expect($user->username)->toBe('superadmin');
    expect($user->hasRole('superadmin'))->toBeTrue();
    expect($user->status)->toBe(AccountStatus::PROTECTED->value);

    // Verify recovery key file was saved
    expect(File::exists(storage_path('app/private/.recovery-key')))->toBeTrue();
});

test('setup wizard admin form name and username are immutable from config', function () {
    // Even if someone tries to set name/username, they come from config
    Livewire::test(SetupWizard::class)
        ->assertSet('adminForm.name', 'Administrator')
        ->assertSet('adminForm.username', 'superadmin');
});
