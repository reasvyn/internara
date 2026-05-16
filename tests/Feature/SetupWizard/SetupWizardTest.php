<?php

declare(strict_types=1);

use App\Livewire\Setup\SetupWizard;
use App\Models\School;
use App\Models\Setup;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Setup::query()->delete();
    Setup::factory()->create(['is_installed' => false]);
});

describe('wizard flow', function () {

    it('mounts without redirect when not installed', function () {
        Livewire::test(SetupWizard::class)
            ->assertSet('currentStep', 1);
    });

    it('cannot proceed from step 1 if audit fails', function () {
        Livewire::test(SetupWizard::class)
            ->set('auditPassed', false)
            ->call('nextStep')
            ->assertSet('currentStep', 1);
    });

    it('navigates to next step', function () {
        Livewire::test(SetupWizard::class)
            ->set('auditPassed', true)
            ->call('nextStep')
            ->assertSet('currentStep', 2);
    });

    it('navigates back to previous step', function () {
        Livewire::test(SetupWizard::class)
            ->set('auditPassed', true)
            ->call('nextStep')
            ->call('prevStep')
            ->assertSet('currentStep', 1);
    });

    it('cannot go before step 1', function () {
        Livewire::test(SetupWizard::class)
            ->set('auditPassed', true)
            ->call('nextStep')
            ->set('currentStep', 1)
            ->call('prevStep')
            ->assertSet('currentStep', 1);
    });

    it('goes to a specific previous step', function () {
        Livewire::test(SetupWizard::class)
            ->set('auditPassed', true)
            ->call('nextStep')
            ->call('nextStep')
            ->call('goToStep', 'welcome')
            ->assertSet('currentStep', 1);
    });

});

describe('step 2: school validation', function () {

    beforeEach(function () {
        Livewire::test(SetupWizard::class)
            ->set('auditPassed', true)
            ->call('nextStep');
    });

    it('requires school name', function () {
        Livewire::test(SetupWizard::class)
            ->set('currentStep', 2)
            ->call('nextStep')
            ->assertHasErrors(['schoolData.name' => 'required']);
    });

    it('requires institutional code', function () {
        Livewire::test(SetupWizard::class)
            ->set('currentStep', 2)
            ->set('schoolData.name', 'SMKN 1 Jakarta')
            ->call('nextStep')
            ->assertHasErrors(['schoolData.institutional_code' => 'required']);
    });

    it('requires school email', function () {
        Livewire::test(SetupWizard::class)
            ->set('currentStep', 2)
            ->set('schoolData.name', 'SMKN 1 Jakarta')
            ->set('schoolData.institutional_code', '10293847')
            ->call('nextStep')
            ->assertHasErrors(['schoolData.email' => 'required']);
    });

    it('proceeds with valid school data', function () {
        Livewire::test(SetupWizard::class)
            ->set('currentStep', 2)
            ->set('schoolData.name', 'SMKN 1 Jakarta')
            ->set('schoolData.institutional_code', '10293847')
            ->set('schoolData.email', 'info@smkn1jkt.sch.id')
            ->call('nextStep')
            ->assertSet('currentStep', 3);
    });

});

describe('step 3: department validation', function () {

    it('requires department name', function () {
        Livewire::test(SetupWizard::class)
            ->set('currentStep', 3)
            ->call('nextStep')
            ->assertHasErrors(['departmentData.name' => 'required']);
    });

    it('proceeds with valid department data', function () {
        Livewire::test(SetupWizard::class)
            ->set('currentStep', 3)
            ->set('departmentData.name', 'Rekayasa Perangkat Lunak')
            ->call('nextStep')
            ->assertSet('currentStep', 4);
    });

});

describe('step 4: admin account validation', function () {

    it('requires admin email', function () {
        Livewire::test(SetupWizard::class)
            ->set('currentStep', 4)
            ->call('nextStep')
            ->assertHasErrors(['adminData.email' => 'required']);
    });

    it('requires admin password', function () {
        Livewire::test(SetupWizard::class)
            ->set('currentStep', 4)
            ->set('adminData.email', 'admin@school.sch.id')
            ->call('nextStep')
            ->assertHasErrors(['adminData.password' => 'required']);
    });

    it('validates password minimum length', function () {
        Livewire::test(SetupWizard::class)
            ->set('currentStep', 4)
            ->set('adminData.email', 'admin@school.sch.id')
            ->set('adminData.password', 'short')
            ->call('nextStep')
            ->assertHasErrors(['adminData.password' => 'min']);
    });

    it('proceeds with valid admin data', function () {
        Livewire::test(SetupWizard::class)
            ->set('currentStep', 4)
            ->set('adminData.email', 'admin@school.sch.id')
            ->set('adminData.password', 'password123')
            ->call('nextStep')
            ->assertSet('currentStep', 5);
    });

});

describe('step 5: internship validation', function () {

    it('requires internship name', function () {
        Livewire::test(SetupWizard::class)
            ->set('currentStep', 5)
            ->call('nextStep')
            ->assertHasErrors(['internshipData.name' => 'required']);
    });

    it('proceeds with valid internship data', function () {
        Livewire::test(SetupWizard::class)
            ->set('currentStep', 5)
            ->set('internshipData.name', 'PKL Semester Ganjil 2026/2027')
            ->set('internshipData.start_date', '2026-07-01')
            ->set('internshipData.end_date', '2026-12-31')
            ->call('nextStep')
            ->assertSet('currentStep', 6);
    });

});

describe('finish: complete setup', function () {

    it('requires confirmation checkboxes', function () {
        Livewire::test(SetupWizard::class)
            ->set('currentStep', 6)
            ->call('finish')
            ->assertHasErrors([
                'dataVerified' => 'accepted',
                'securityAware' => 'accepted',
            ]);
    });

    it('creates school, department, admin and finalizes', function () {
        Role::create(['name' => 'super_admin', 'guard_name' => 'web']);

        Livewire::test(SetupWizard::class)
            ->assertSuccessful()
            ->set('auditPassed', true)
            ->set('schoolData.name', 'SMKN 1 Jakarta')
            ->set('schoolData.institutional_code', '10293847')
            ->set('schoolData.address', 'Jl. Merdeka No. 1')
            ->set('schoolData.email', 'info@smkn1jkt.sch.id')
            ->set('departmentData.name', 'Rekayasa Perangkat Lunak')
            ->set('adminData.name', 'Primary Admin')
            ->set('adminData.username', 'primaryadmin')
            ->set('adminData.email', 'primaryadmin@school.sch.id')
            ->set('adminData.password', 'password123')
            ->set('adminData.password_confirmation', 'password123')
            ->set('internshipData.name', 'PKL 2026/2027')
            ->set('internshipData.start_date', '2026-07-01')
            ->set('internshipData.end_date', '2026-12-31')
            ->set('dataVerified', true)
            ->set('securityAware', true)
            ->call('finish')
            ->assertHasNoErrors();

        expect(School::count())->toBe(1);
        expect(User::where('email', 'primaryadmin@school.sch.id')->exists())->toBeTrue();
        expect(Setup::first()->is_installed)->toBeTrue();
    });

});

describe('state persistence', function () {

    it('loads default admin username from config', function () {
        Livewire::test(SetupWizard::class)
            ->assertSet('adminData.username', config('setup.defaults.admin_username'));
    });

    it('saves form data to session', function () {
        Livewire::test(SetupWizard::class)
            ->set('schoolData.name', 'SMKN 1 Jakarta')
            ->assertSessionHas('setup.form_data');
    });

});
