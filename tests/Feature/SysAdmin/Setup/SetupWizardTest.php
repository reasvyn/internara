<?php

declare(strict_types=1);

namespace Tests\Feature\SysAdmin\Setup;

use App\Academics\AcademicYear\Models\AcademicYear;
use App\Program\Internship\Models\Internship;
use App\SysAdmin\Setup\Livewire\SetupWizard;
use App\SysAdmin\Setup\Models\Setup;
use App\User\Enums\AccountStatus;
use App\User\Enums\Role as RoleEnum;
use App\User\Models\User;
use Database\Seeders\AcademicYearSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
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

/*
|--------------------------------------------------------------------------
| Step Navigation (new order: welcome > account > school > department > internship > finalize > complete)
|--------------------------------------------------------------------------
*/

test('setup wizard navigation works correctly step-by-step', function () {
    Livewire::test(SetupWizard::class)
        ->assertSet('currentStep', 1)
        // Step 1 -> Step 2 (Admin)
        ->call('nextStep')
        ->assertSet('currentStep', 2)

        // Step 2 (Admin validation fails if invalid email/password)
        ->set('adminForm.email', 'invalid-email')
        ->set('adminForm.password', 'short')
        ->call('nextStep')
        ->assertHasErrors(['adminForm.email', 'adminForm.password'])
        ->assertSet('currentStep', 2)

        // Fill Step 2
        ->set('adminForm.email', 'admin@example.com')
        ->set('adminForm.password', 'SecurePassword123!')
        ->set('adminForm.password_confirmation', 'SecurePassword123!')
        ->call('nextStep')
        ->assertHasNoErrors()
        ->assertSet('currentStep', 3)

        // Step 3 (School form validation fails if blank)
        ->set('schoolForm.name', '')
        ->call('nextStep')
        ->assertHasErrors(['schoolForm.name'])
        ->assertSet('currentStep', 3)

        // Fill Step 3
        ->set('schoolForm.name', 'Test School')
        ->set('schoolForm.institutional_code', 'SCH123')
        ->set('schoolForm.address', '123 School St')
        ->set('schoolForm.email', 'school@example.com')
        ->set('schoolForm.phone', '0812345678')
        ->set('schoolForm.website', 'https://school.example.com')
        ->set('schoolForm.principal_name', 'Dr. Principal')
        ->call('nextStep')
        ->assertHasNoErrors()
        ->assertSet('currentStep', 4)

        // Step 4 (Department validation fails if blank)
        ->set('departmentForm.name', '')
        ->call('nextStep')
        ->assertHasErrors(['departmentForm.name'])
        ->assertSet('currentStep', 4)

        // Fill Step 4
        ->set('departmentForm.name', 'Computer Science')
        ->set('departmentForm.description', 'CS Dept')
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
        // Step 2: Admin
        ->set('adminForm.email', 'admin@example.com')
        ->set('adminForm.password', 'SecurePassword123!')
        ->set('adminForm.password_confirmation', 'SecurePassword123!')
        // Step 3: School
        ->set('schoolForm.name', 'Test School')
        ->set('schoolForm.institutional_code', 'SCH123')
        ->set('schoolForm.address', '123 School St')
        ->set('schoolForm.email', 'school@example.com')
        ->set('schoolForm.phone', '0812345678')
        ->set('schoolForm.website', 'https://school.example.com')
        ->set('schoolForm.principal_name', 'Dr. Principal')
        // Step 4: Department
        ->set('departmentForm.name', 'Computer Science')
        ->set('departmentForm.description', 'CS Dept')
        // Step 6: Finalize (Accept checkboxes)
        ->set('dataVerified', true)
        ->set('securityAware', true)
        ->call('finish')
        ->assertHasNoErrors()
        ->assertSet('currentStep', 7);

    $freshSetup = Setup::first();
    expect($freshSetup->is_installed)->toBeTrue();
    expect($freshSetup->school_id)->not->toBeNull();
    expect($freshSetup->department_id)->not->toBeNull();
    expect($freshSetup->recovery_key)->not->toBeNull();

    $user = User::where('email', 'admin@example.com')->first();
    expect($user)->not->toBeNull();
    expect($user->name)->toBe('Administrator');
    expect($user->username)->toBe('superadmin');
    expect($user->hasRole('superadmin'))->toBeTrue();
    expect($user->status)->toBe(AccountStatus::PROTECTED->value);
});

/*
|--------------------------------------------------------------------------
| Step Validation Tests (new order)
|--------------------------------------------------------------------------
*/

test('step 1 blocks progression when audit has not passed', function () {
    Livewire::test(SetupWizard::class)
        ->set('auditPassed', false)
        ->call('nextStep')
        ->assertSet('currentStep', 1);
});

test('step 2 admin form requires password confirmation match', function () {
    Livewire::test(SetupWizard::class)
        ->call('nextStep')
        ->assertSet('currentStep', 2)
        ->set('adminForm.email', 'admin@test.com')
        ->set('adminForm.password', 'SecurePassword123!')
        ->set('adminForm.password_confirmation', 'DifferentPassword123!')
        ->call('nextStep')
        ->assertHasErrors(['adminForm.password'])
        ->assertSet('currentStep', 2);
});

test('step 2 admin form requires password complexity (mixed case + numbers)', function () {
    Livewire::test(SetupWizard::class)
        ->call('nextStep')
        ->assertSet('currentStep', 2)
        ->set('adminForm.email', 'admin@test.com')
        ->set('adminForm.password', 'simple')
        ->set('adminForm.password_confirmation', 'simple')
        ->call('nextStep')
        ->assertHasErrors(['adminForm.password'])
        ->assertSet('currentStep', 2);
});

test('step 3 school form rejects invalid email format', function () {
    Livewire::test(SetupWizard::class)
        ->call('nextStep')
        ->set('adminForm.email', 'admin@school.test')
        ->set('adminForm.password', 'SecurePass123!')
        ->set('adminForm.password_confirmation', 'SecurePass123!')
        ->call('nextStep')
        ->assertSet('currentStep', 3)
        ->set('schoolForm.name', 'Test School')
        ->set('schoolForm.institutional_code', 'SCH123')
        ->set('schoolForm.email', 'not-an-email')
        ->call('nextStep')
        ->assertHasErrors(['schoolForm.email'])
        ->assertSet('currentStep', 3);
});

test('step 3 school form accepts minimal required fields', function () {
    Livewire::test(SetupWizard::class)
        ->call('nextStep')
        ->set('adminForm.email', 'admin@school.test')
        ->set('adminForm.password', 'SecurePass123!')
        ->set('adminForm.password_confirmation', 'SecurePass123!')
        ->call('nextStep')
        ->assertSet('currentStep', 3)
        ->set('schoolForm.name', 'Minimal School')
        ->set('schoolForm.institutional_code', 'MIN01')
        ->set('schoolForm.email', 'school@test.com')
        ->call('nextStep')
        ->assertHasNoErrors()
        ->assertSet('currentStep', 4);
});

test('step 4 department form rejects empty name', function () {
    Livewire::test(SetupWizard::class)
        ->call('nextStep')
        ->set('adminForm.email', 'admin@school.test')
        ->set('adminForm.password', 'SecurePass123!')
        ->set('adminForm.password_confirmation', 'SecurePass123!')
        ->call('nextStep')
        ->set('schoolForm.name', 'Test School')
        ->set('schoolForm.institutional_code', 'SCH01')
        ->set('schoolForm.email', 'school@test.com')
        ->call('nextStep')
        ->assertSet('currentStep', 4)
        ->set('departmentForm.name', '')
        ->call('nextStep')
        ->assertHasErrors(['departmentForm.name'])
        ->assertSet('currentStep', 4);
});

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
        'completed_steps' => ['welcome', 'account', 'school', 'department'],
    ]);

    Livewire::test(SetupWizard::class)
        ->call('nextStep') // to step 2
        ->set('adminForm.email', 'admin@school.test')
        ->set('adminForm.password', 'SecurePass123!')
        ->set('adminForm.password_confirmation', 'SecurePass123!')
        ->call('nextStep') // to step 3
        ->set('schoolForm.name', 'Test School')
        ->set('schoolForm.institutional_code', 'SCH01')
        ->set('schoolForm.email', 'school@test.com')
        ->call('nextStep') // to step 4
        ->set('departmentForm.name', 'CS Dept')
        ->call('nextStep') // to step 5
        ->assertSet('currentStep', 5)
        // Navigate back to step 3 (school)
        ->call('goToStep', 'school')
        ->assertSet('currentStep', 3);
});

test('goToStep ignores invalid step keys', function () {
    Livewire::test(SetupWizard::class)
        ->assertSet('currentStep', 1)
        ->call('goToStep', 'nonexistent_step')
        ->assertSet('currentStep', 1);
});

/*
|--------------------------------------------------------------------------
| Session State Tests
|--------------------------------------------------------------------------
*/

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
    Livewire::test(SetupWizard::class)
        ->set('adminForm.email', 'admin@school.test')
        ->set('adminForm.password', 'SecurePass123!')
        ->set('adminForm.password_confirmation', 'SecurePass123!')
        ->set('schoolForm.name', 'Test School')
        ->set('schoolForm.institutional_code', 'SCH01')
        ->set('schoolForm.email', 'school@test.com')
        ->set('departmentForm.name', 'CS Dept')
        ->set('dataVerified', true)
        ->set('securityAware', true)
        ->call('finish')
        ->assertSet('currentStep', 7);

    expect(Setup::first()->is_installed)->toBeTrue();

    session()->forget('setup.completed');

    Livewire::test(SetupWizard::class)
        ->assertRedirect(route('login'));
});

/*
|--------------------------------------------------------------------------
| Full Flow with Internship Data
|--------------------------------------------------------------------------
*/

test('setup wizard finishes with internship data creating all entities', function () {
    $this->seed(AcademicYearSeeder::class);

    Livewire::test(SetupWizard::class)
        ->set('adminForm.email', 'admin@pkl.com')
        ->set('adminForm.password', 'AdminPKL2026!')
        ->set('adminForm.password_confirmation', 'AdminPKL2026!')
        ->set('schoolForm.name', 'PKL School')
        ->set('schoolForm.institutional_code', 'PKL01')
        ->set('schoolForm.email', 'pkl@test.com')
        ->set('departmentForm.name', 'Teknik Informatika')
        ->set('departmentForm.description', 'Jurusan TI')
        ->set('internshipForm.name', 'PKL Semester Genap 2026')
        ->set('internshipForm.description', 'Program PKL')
        ->set('internshipForm.start_date', '2026-07-01')
        ->set('internshipForm.end_date', '2026-12-31')
        ->set('dataVerified', true)
        ->set('securityAware', true)
        ->call('finish')
        ->assertHasNoErrors()
        ->assertSet('currentStep', 7);

    $setup = Setup::first();
    expect($setup->is_installed)->toBeTrue();
    expect($setup->school_id)->not->toBeNull();
    expect($setup->department_id)->not->toBeNull();
    expect($setup->recovery_key)->not->toBeNull();
    expect($setup->setup_token)->toBeNull();

    $internship = Internship::first();
    expect($internship)->not->toBeNull();
    expect($internship->name)->toBe('PKL Semester Genap 2026');
    $activeYear = AcademicYear::where('is_active', true)->first();
    expect($activeYear)->not->toBeNull();
    expect($internship->academic_year_id)->toBe($activeYear->id);

    $user = User::where('email', 'admin@pkl.com')->first();
    expect($user)->not->toBeNull();
    expect($user->name)->toBe('Administrator');
    expect($user->username)->toBe('superadmin');
    expect($user->hasRole('superadmin'))->toBeTrue();
    expect($user->status)->toBe(AccountStatus::PROTECTED->value);

    expect(File::exists(storage_path('app/private/.recovery-key')))->toBeTrue();
});

test('setup wizard admin form name and username are immutable from config', function () {
    Livewire::test(SetupWizard::class)
        ->assertSet('adminForm.name', 'Administrator')
        ->assertSet('adminForm.username', 'superadmin');
});
