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
            ->assertHasErrors(['schoolName' => 'required']);
    });

    it('requires institutional code', function () {
        Livewire::test(SetupWizard::class)
            ->set('currentStep', 2)
            ->set('schoolName', 'SMKN 1 Jakarta')
            ->call('nextStep')
            ->assertHasErrors(['institutionalCode' => 'required']);
    });

    it('requires school email', function () {
        Livewire::test(SetupWizard::class)
            ->set('currentStep', 2)
            ->set('schoolName', 'SMKN 1 Jakarta')
            ->set('institutionalCode', '10293847')
            ->call('nextStep')
            ->assertHasErrors(['schoolEmail' => 'required']);
    });

    it('proceeds with valid school data', function () {
        Livewire::test(SetupWizard::class)
            ->set('currentStep', 2)
            ->set('schoolName', 'SMKN 1 Jakarta')
            ->set('institutionalCode', '10293847')
            ->set('schoolEmail', 'info@smkn1jkt.sch.id')
            ->call('nextStep')
            ->assertSet('currentStep', 3);
    });

});

describe('step 3: department validation', function () {

    it('requires department name', function () {
        Livewire::test(SetupWizard::class)
            ->set('currentStep', 3)
            ->call('nextStep')
            ->assertHasErrors(['departmentName' => 'required']);
    });

    it('proceeds with valid department data', function () {
        Livewire::test(SetupWizard::class)
            ->set('currentStep', 3)
            ->set('departmentName', 'Rekayasa Perangkat Lunak')
            ->call('nextStep')
            ->assertSet('currentStep', 4);
    });

});

describe('step 4: admin account validation', function () {

    it('requires admin name', function () {
        Livewire::test(SetupWizard::class)
            ->set('currentStep', 4)
            ->call('nextStep')
            ->assertHasErrors(['adminName' => 'required']);
    });

    it('requires admin email', function () {
        Livewire::test(SetupWizard::class)
            ->set('currentStep', 4)
            ->set('adminName', 'Admin User')
            ->call('nextStep')
            ->assertHasErrors(['adminEmail' => 'required']);
    });

    it('requires admin password', function () {
        Livewire::test(SetupWizard::class)
            ->set('currentStep', 4)
            ->set('adminName', 'Admin User')
            ->set('adminEmail', 'admin@school.sch.id')
            ->call('nextStep')
            ->assertHasErrors(['adminPassword' => 'required']);
    });

    it('validates password confirmation', function () {
        Livewire::test(SetupWizard::class)
            ->set('currentStep', 4)
            ->set('adminName', 'Admin User')
            ->set('adminEmail', 'admin@school.sch.id')
            ->set('adminPassword', 'password123')
            ->set('adminPassword_confirmation', 'different')
            ->call('nextStep')
            ->assertHasErrors(['adminPassword' => 'confirmed']);
    });

    it('validates password minimum length', function () {
        Livewire::test(SetupWizard::class)
            ->set('currentStep', 4)
            ->set('adminName', 'Admin User')
            ->set('adminEmail', 'admin@school.sch.id')
            ->set('adminPassword', 'short')
            ->set('adminPassword_confirmation', 'short')
            ->call('nextStep')
            ->assertHasErrors(['adminPassword' => 'min']);
    });

    it('proceeds with valid admin data', function () {
        Livewire::test(SetupWizard::class)
            ->set('currentStep', 4)
            ->set('adminName', 'Admin User')
            ->set('adminEmail', 'admin@school.sch.id')
            ->set('adminPassword', 'password123')
            ->set('adminPassword_confirmation', 'password123')
            ->call('nextStep')
            ->assertSet('currentStep', 5);
    });

});

describe('step 5: internship validation', function () {

    it('requires internship name', function () {
        Livewire::test(SetupWizard::class)
            ->set('currentStep', 5)
            ->call('nextStep')
            ->assertHasErrors(['internshipName' => 'required']);
    });

    it('proceeds with valid internship data', function () {
        Livewire::test(SetupWizard::class)
            ->set('currentStep', 5)
            ->set('internshipName', 'PKL Semester Ganjil 2026/2027')
            ->set('startDate', '2026-07-01')
            ->set('endDate', '2026-12-31')
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
            ->set('schoolName', 'SMKN 1 Jakarta')
            ->set('institutionalCode', '10293847')
            ->set('schoolAddress', 'Jl. Merdeka No. 1')
            ->set('schoolEmail', 'info@smkn1jkt.sch.id')
            ->set('departmentName', 'Rekayasa Perangkat Lunak')
            ->set('adminName', 'Primary Admin')
            ->set('adminUsername', 'primaryadmin')
            ->set('adminEmail', 'primaryadmin@school.sch.id')
            ->set('adminPassword', 'password123')
            ->set('adminPassword_confirmation', 'password123')
            ->set('internshipName', 'PKL 2026/2027')
            ->set('startDate', '2026-07-01')
            ->set('endDate', '2026-12-31')
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

    it('generates username from admin name', function () {
        Livewire::test(SetupWizard::class)
            ->set('adminName', 'John Doe')
            ->assertSet('adminUsername', 'johndoe');
    });

    it('saves form data to session', function () {
        Livewire::test(SetupWizard::class)
            ->set('schoolName', 'SMKN 1 Jakarta')
            ->assertSessionHas('setup.form_data');
    });

});
