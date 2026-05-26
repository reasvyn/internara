<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

use App\Domain\Internship\Models\Internship;
use App\Domain\School\Models\Department;
use App\Domain\School\Models\School;
use App\Domain\Setup\Livewire\SetupWizard;
use App\Domain\Setup\Models\Setup;
use App\Domain\User\Models\User;
use Spatie\Permission\Models\Role;

// ─── Mount / Initialization ────────────────────────────────────────────────

describe('mount', function () {
    it('starts at step 1', function () {
        Setup::query()->delete();
        Setup::create(['is_installed' => false]);

        Livewire::test(SetupWizard::class)
            ->assertSet('currentStep', 1);
    });

    it('redirects to login if already installed', function () {
        Setup::query()->delete();
        Setup::create(['is_installed' => true]);

        Livewire::test(SetupWizard::class)
            ->assertRedirect(route('login'));
    });

    it('pre-fills admin name and username from config', function () {
        Setup::query()->delete();
        Setup::create(['is_installed' => false]);

        Livewire::test(SetupWizard::class)
            ->assertSet('adminForm.name', config('setup.defaults.admin_name', 'Administrator'))
            ->assertSet('adminForm.username', config('setup.defaults.admin_username', 'superadmin'));
    });

    it('runs environment audit on mount', function () {
        Setup::query()->delete();
        Setup::create(['is_installed' => false]);

        Livewire::test(SetupWizard::class)
            ->assertSet('auditPassed', true);
    });

    it('restores previously saved form state from session', function () {
        Setup::query()->delete();
        Setup::create(['is_installed' => false]);

        session()->put('setup.form_data', [
            'school' => ['name' => 'SMK Restored', 'institutional_code' => '001', 'email' => 'a@b.com'],
            'department' => ['name' => 'Dept Restored'],
            'admin' => ['name' => 'Admin', 'username' => 'sa', 'email' => 'adm@b.com'],
            'internship' => ['name' => 'Internship Restored'],
        ]);

        Livewire::test(SetupWizard::class)
            ->assertSet('schoolForm.name', 'SMK Restored')
            ->assertSet('departmentForm.name', 'Dept Restored')
            ->assertSet('adminForm.email', 'adm@b.com')
            ->assertSet('internshipForm.name', 'Internship Restored');
    });
});

// ─── Form State Persistence ────────────────────────────────────────────────

describe('form persistence', function () {
    it('saves school data to session on update', function () {
        Setup::query()->delete();
        Setup::create(['is_installed' => false]);

        Livewire::test(SetupWizard::class)
            ->set('schoolForm.name', 'SMK Negeri 1');

        expect(session('setup.form_data.school.name'))->toBe('SMK Negeri 1');
    });

    it('saves admin email (excluding password) to session on update', function () {
        Setup::query()->delete();
        Setup::create(['is_installed' => false]);

        Livewire::test(SetupWizard::class)
            ->set('adminForm.email', 'admin@school.sch.id');

        expect(session('setup.form_data.admin.email'))->toBe('admin@school.sch.id');
        expect(session('setup.form_data.admin'))->not->toHaveKey('password');
    });
});

// ─── Step Navigation ──────────────────────────────────────────────────────

describe('step navigation', function () {
    it('advances past audit when it passes', function () {
        Setup::query()->delete();
        Setup::create(['is_installed' => false]);

        Livewire::test(SetupWizard::class)
            ->call('nextStep')
            ->assertSet('currentStep', 2);
    });

    it('blocks advancement when audit fails', function () {
        Setup::query()->delete();
        Setup::create(['is_installed' => false]);

        Livewire::test(SetupWizard::class)
            ->set('auditPassed', false)
            ->call('nextStep')
            ->assertSet('currentStep', 1);
    });

    it('goes back one step', function () {
        Setup::query()->delete();
        Setup::create(['is_installed' => false]);

        Livewire::test(SetupWizard::class)
            ->call('nextStep')
            ->call('prevStep')
            ->assertSet('currentStep', 1);
    });

    it('navigates to step 3 with school form filled', function () {
        Setup::query()->delete();
        Setup::create(['is_installed' => false]);

        Livewire::test(SetupWizard::class)
            ->call('nextStep')
            ->set('schoolForm.name', 'SMK 1')
            ->set('schoolForm.institutional_code', '001')
            ->set('schoolForm.email', 'a@b.com')
            ->call('nextStep')
            ->assertSet('currentStep', 3);
    });

    it('blocks step 3 if school name is empty', function () {
        Setup::query()->delete();
        Setup::create(['is_installed' => false]);

        Livewire::test(SetupWizard::class)
            ->call('nextStep')
            ->call('nextStep')
            ->assertSet('currentStep', 2);
    });

    it('navigates to step 5 with school+dept+admin filled', function () {
        Setup::query()->delete();
        Setup::create(['is_installed' => false]);

        Livewire::test(SetupWizard::class)
            ->call('nextStep')
            ->set('schoolForm.name', 'S')->set('schoolForm.institutional_code', '1')->set('schoolForm.email', 'a@b.com')
            ->call('nextStep')
            ->set('departmentForm.name', 'TKI')
            ->call('nextStep')
            ->set('adminForm.email', 'a@b.com')
            ->set('adminForm.password', 'Secure1Pass')
            ->set('adminForm.password_confirmation', 'Secure1Pass')
            ->call('nextStep')
            ->assertSet('currentStep', 5);
    });

    it('navigates to step 6 from internship without filling (optional)', function () {
        Setup::query()->delete();
        Setup::create(['is_installed' => false]);

        Livewire::test(SetupWizard::class)
            ->call('nextStep')
            ->set('schoolForm.name', 'S')->set('schoolForm.institutional_code', '1')->set('schoolForm.email', 'a@b.com')
            ->call('nextStep')
            ->set('departmentForm.name', 'TKI')
            ->call('nextStep')
            ->set('adminForm.email', 'a@b.com')
            ->set('adminForm.password', 'Secure1Pass')
            ->set('adminForm.password_confirmation', 'Secure1Pass')
            ->call('nextStep')
            ->call('nextStep')
            ->assertSet('currentStep', 6);
    });
});

// ─── Validation ────────────────────────────────────────────────────────────

describe('validation', function () {
    it('school step requires name, code, email', function () {
        Setup::query()->delete();
        Setup::create(['is_installed' => false]);

        Livewire::test(SetupWizard::class)
            ->call('nextStep')
            ->set('schoolForm.name', '')->set('schoolForm.institutional_code', '')->set('schoolForm.email', '')
            ->call('nextStep')
            ->assertSet('currentStep', 2)
            ->assertHasErrors(['schoolForm.name', 'schoolForm.institutional_code', 'schoolForm.email']);
    });

    it('department step requires name', function () {
        Setup::query()->delete();
        Setup::create(['is_installed' => false]);

        Livewire::test(SetupWizard::class)
            ->call('nextStep')
            ->set('schoolForm.name', 'S')->set('schoolForm.institutional_code', '1')->set('schoolForm.email', 'a@b.com')
            ->call('nextStep')
            ->set('departmentForm.name', '')
            ->call('nextStep')
            ->assertSet('currentStep', 3)
            ->assertHasErrors(['departmentForm.name']);
    });

    it('admin step requires email and password', function () {
        Setup::query()->delete();
        Setup::create(['is_installed' => false]);

        Livewire::test(SetupWizard::class)
            ->call('nextStep')
            ->set('schoolForm.name', 'S')->set('schoolForm.institutional_code', '1')->set('schoolForm.email', 'a@b.com')
            ->call('nextStep')
            ->set('departmentForm.name', 'TKI')
            ->call('nextStep')
            ->set('adminForm.email', '')->set('adminForm.password', '')->set('adminForm.password_confirmation', '')
            ->call('nextStep')
            ->assertSet('currentStep', 4)
            ->assertHasErrors(['adminForm.email', 'adminForm.password']);
    });

    it('admin step rejects mismatched password confirmation', function () {
        Setup::query()->delete();
        Setup::create(['is_installed' => false]);

        Livewire::test(SetupWizard::class)
            ->call('nextStep')
            ->set('schoolForm.name', 'S')->set('schoolForm.institutional_code', '1')->set('schoolForm.email', 'a@b.com')
            ->call('nextStep')
            ->set('departmentForm.name', 'TKI')
            ->call('nextStep')
            ->set('adminForm.email', 'admin@school.com')
            ->set('adminForm.password', 'Secure1Pass')
            ->set('adminForm.password_confirmation', 'Different1')
            ->call('nextStep')
            ->assertSet('currentStep', 4)
            ->assertHasErrors(['adminForm.password']);
    });

    it('finalize step requires data and security acknowledgements', function () {
        Setup::query()->delete();
        Setup::create(['is_installed' => false]);

        Livewire::test(SetupWizard::class)
            ->set('schoolForm.name', 'S')->set('schoolForm.institutional_code', '1')->set('schoolForm.email', 'a@b.com')
            ->set('departmentForm.name', 'TKI')
            ->set('adminForm.email', 'a@b.com')
            ->set('adminForm.password', 'Secure1Pass')
            ->set('adminForm.password_confirmation', 'Secure1Pass')
            ->set('dataVerified', false)->set('securityAware', false)
            ->call('finish')
            ->assertHasErrors(['dataVerified', 'securityAware']);
    });
});

// ─── End-to-End SetupWizard finish() ──────────────────────────────────────

describe('finish', function () {
    beforeEach(function () {
        foreach (['super_admin', 'admin', 'student', 'teacher', 'supervisor'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }
    });

    it('completes setup and transitions to step 7', function () {
        Setup::query()->delete();
        Setup::create(['is_installed' => false]);

        Livewire::test(SetupWizard::class)
            ->call('nextStep')
            ->set('schoolForm.name', 'SMK 1')
            ->set('schoolForm.institutional_code', '001')
            ->set('schoolForm.email', 'a@b.com')
            ->call('nextStep')
            ->set('departmentForm.name', 'TKI')
            ->call('nextStep')
            ->set('adminForm.email', 'admin@test.com')
            ->set('adminForm.password', 'Secure1Pass')
            ->set('adminForm.password_confirmation', 'Secure1Pass')
            ->call('nextStep')
            ->call('nextStep')
            ->set('dataVerified', true)
            ->set('securityAware', true)
            ->call('finish')
            ->assertSet('currentStep', 7)
            ->assertSet('recoveryKey', fn ($key) => is_string($key) && $key !== '')
            ->assertHasNoErrors();
    });

    it('creates school, department, admin, and internship records', function () {
        Setup::query()->delete();
        Setup::create(['is_installed' => false]);

        Livewire::test(SetupWizard::class)
            ->call('nextStep')
            ->set('schoolForm.name', 'SMK Negeri 2')
            ->set('schoolForm.institutional_code', '002')
            ->set('schoolForm.email', 'info@smkn2.sch.id')
            ->call('nextStep')
            ->set('departmentForm.name', 'Multimedia')
            ->call('nextStep')
            ->set('adminForm.email', 'admin@smkn2.sch.id')
            ->set('adminForm.password', 'Secure1Pass')
            ->set('adminForm.password_confirmation', 'Secure1Pass')
            ->call('nextStep')
            ->set('internshipForm.name', 'PKL 2026')
            ->set('internshipForm.start_date', '2026-07-01')
            ->set('internshipForm.end_date', '2026-12-31')
            ->call('nextStep')
            ->set('dataVerified', true)
            ->set('securityAware', true)
            ->call('finish')
            ->assertSet('currentStep', 7);

        expect(School::count())->toBe(1);
        expect(Department::count())->toBe(1);
        expect(User::count())->toBe(1);
        expect(Internship::count())->toBe(1);
        expect(Setup::first()->is_installed)->toBeTrue();
    });
});
