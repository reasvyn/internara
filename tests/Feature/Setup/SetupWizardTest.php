<?php

declare(strict_types=1);

use App\Domain\Auth\Enums\Role;
use App\Domain\School\Models\School;
use App\Domain\Setup\Actions\GenerateSetupTokenAction;
use App\Domain\Setup\Livewire\SetupWizard;
use App\Domain\Setup\Models\Setup;
use Spatie\Permission\Models\Role as RoleModel;

beforeEach(function () {
    app()->setLocale('en');
    RoleModel::create(['name' => Role::SUPER_ADMIN->value, 'guard_name' => 'web']);
    RoleModel::create(['name' => Role::ADMIN->value, 'guard_name' => 'web']);
    Setup::truncate();
    Setup::create(['is_installed' => false, 'completed_steps' => []]);
    School::truncate();
});

describe('SetupWizard', function () {
    it('mounts and runs audit', function () {
        Livewire::test(SetupWizard::class)
            ->assertSet('currentStep', 1)
            ->assertSet('auditPassed', true);
    });

    it('redirects to login when already installed', function () {
        Setup::truncate();
        Setup::create(['is_installed' => true, 'completed_steps' => []]);

        Livewire::test(SetupWizard::class)
            ->assertRedirect(route('login'));
    });

    it('cannot proceed past step 1 when audit fails', function () {
        config(['database.connections.sqlite.database' => '/nonexistent/db.sqlite']);

        $component = Livewire::test(SetupWizard::class);

        $component->assertSet('auditPassed', false)
            ->call('nextStep')
            ->assertSet('currentStep', 1);
    });

    it('navigates forward with valid school data', function () {
        Livewire::test(SetupWizard::class)
            ->set('schoolForm.name', 'Test School')
            ->set('schoolForm.institutional_code', 'TS001')
            ->set('schoolForm.email', 'school@test.com')
            ->set('schoolForm.address', '123 Street')
            ->call('nextStep')
            ->assertSet('currentStep', 2)
            ->set('departmentForm.name', 'Computer Science')
            ->call('nextStep')
            ->assertSet('currentStep', 3);
    });

    it('goes back to previous step', function () {
        Livewire::test(SetupWizard::class)
            ->set('schoolForm.name', 'S')
            ->set('schoolForm.institutional_code', 'S001')
            ->set('schoolForm.email', 's@t.com')
            ->set('schoolForm.address', 'A')
            ->call('nextStep')
            ->call('prevStep')
            ->assertSet('currentStep', 1);
    });

    it('does not go below step 1', function () {
        Livewire::test(SetupWizard::class)
            ->call('prevStep')
            ->assertSet('currentStep', 1);
    });

    it('validates school form on step 2', function () {
        Livewire::test(SetupWizard::class)
            ->call('nextStep')
            ->call('nextStep')
            ->assertHasErrors(['schoolForm.name']);
    });

    it('validates admin form on step 4', function () {
        Livewire::test(SetupWizard::class)
            ->set('schoolForm.name', 'School')
            ->set('schoolForm.institutional_code', 'SC001')
            ->set('schoolForm.email', 's@t.com')
            ->set('schoolForm.address', 'Addr')
            ->call('nextStep')
            ->set('departmentForm.name', 'Dept')
            ->call('nextStep')
            ->call('nextStep')
            ->call('nextStep')
            ->assertHasErrors(['adminForm.email']);
    });

    it('validates department form on step 3', function () {
        Livewire::test(SetupWizard::class)
            ->set('schoolForm.name', 'School')
            ->set('schoolForm.institutional_code', 'SC001')
            ->set('schoolForm.email', 's@t.com')
            ->set('schoolForm.address', 'Addr')
            ->call('nextStep')
            ->call('nextStep')
            ->call('nextStep')
            ->assertHasErrors(['departmentForm.name']);
    });

    it('skips internship validation when form is empty', function () {
        $component = Livewire::test(SetupWizard::class);

        $component->set('schoolForm.name', 'School')
            ->set('schoolForm.institutional_code', 'SC001')
            ->set('schoolForm.email', 's@t.com')
            ->set('schoolForm.address', 'Addr')
            ->call('nextStep')
            ->set('departmentForm.name', 'Dept')
            ->call('nextStep')
            ->set('adminForm.email', 'a@b.com')
            ->set('adminForm.password', 'SecurePass1')
            ->set('adminForm.password_confirmation', 'SecurePass1')
            ->call('nextStep')
            ->call('nextStep')
            ->assertSet('currentStep', 5);

        // step 5: internship empty → skip validation → should proceed
        $component->call('nextStep')
            ->assertSet('currentStep', 6);
    });

    it('validates admin password complexity', function () {
        Livewire::test(SetupWizard::class)
            ->set('schoolForm.name', 'School')
            ->set('schoolForm.institutional_code', 'SC001')
            ->set('schoolForm.email', 's@t.com')
            ->set('schoolForm.address', 'Addr')
            ->call('nextStep')
            ->set('departmentForm.name', 'Dept')
            ->call('nextStep')
            ->set('adminForm.email', 'a@b.com')
            ->set('adminForm.password', 'weak')
            ->set('adminForm.password_confirmation', 'weak')
            ->call('nextStep')
            ->assertHasErrors(['adminForm.password']);
    });

    it('excludes password from session state', function () {
        Livewire::test(SetupWizard::class)
            ->set('schoolForm.name', 'School')
            ->set('schoolForm.institutional_code', 'SC001')
            ->set('schoolForm.email', 's@t.com')
            ->set('schoolForm.address', 'Addr')
            ->call('nextStep')
            ->set('departmentForm.name', 'Dept')
            ->call('nextStep')
            ->set('adminForm.email', 'a@b.com')
            ->set('adminForm.password', 'Secret123')
            ->set('adminForm.password_confirmation', 'Secret123');

        $data = session()->get('setup.form_data', []);

        expect($data['admin'] ?? [])->not->toHaveKey('password');
    });

    it('navigates to previous step via goToStep', function () {
        Livewire::test(SetupWizard::class)
            ->set('schoolForm.name', 'School')
            ->set('schoolForm.institutional_code', 'SC001')
            ->set('schoolForm.email', 's@t.com')
            ->set('schoolForm.address', 'A')
            ->call('nextStep')
            ->call('goToStep', 'welcome')
            ->assertSet('currentStep', 1);
    });

    it('ignores goToStep for nonexistent step', function () {
        Livewire::test(SetupWizard::class)
            ->call('goToStep', 'nonexistent')
            ->assertSet('currentStep', 1);
    });

    it('redirects to login after finishSession', function () {
        Livewire::test(SetupWizard::class)
            ->call('finishSession')
            ->assertRedirect(route('login'));
    });

    it('has default admin name and username from config', function () {
        Livewire::test(SetupWizard::class)
            ->assertSet('adminForm.name', config('setup.defaults.admin_name', 'Administrator'))
            ->assertSet('adminForm.username', config('setup.defaults.admin_username', 'superadmin'));
    });

    it('overrides admin defaults when form is filled', function () {
        Livewire::test(SetupWizard::class)
            ->set('adminForm.name', 'Custom Name')
            ->assertSet('adminForm.name', 'Custom Name')
            ->assertSet('adminForm.username', config('setup.defaults.admin_username', 'superadmin'));
    });

    it('returns wizard title', function () {
        Livewire::test(SetupWizard::class)
            ->assertSet('currentStep', 1)
            ->assertSee('Internara');
    });

    it('saves form state to session on field change', function () {
        Livewire::test(SetupWizard::class)
            ->set('schoolForm.name', 'Session School')
            ->assertSessionHas('setup.form_data');
    });

    it('restores form state from session on mount', function () {
        session()->put('setup.form_data', [
            'school' => ['name' => 'Restored School', 'institutional_code' => 'RS001', 'address' => 'Restored St', 'email' => '', 'phone' => '', 'website' => '', 'principal_name' => ''],
            'department' => ['name' => '', 'description' => ''],
            'admin' => ['name' => 'Admin User', 'username' => 'admin', 'email' => 'admin@restored.test'],
            'internship' => ['name' => '', 'description' => '', 'start_date' => '', 'end_date' => ''],
        ]);

        Livewire::test(SetupWizard::class)
            ->assertSet('schoolForm.name', 'Restored School');
    });

    it('saves state on internship field change', function () {
        Livewire::test(SetupWizard::class)
            ->set('internshipForm.name', 'PKL 2025');

        $data = session()->get('setup.form_data');

        expect($data['internship']['name'])->toBe('PKL 2025');
    });

});

describe('SetupWizard route', function () {
    it('returns 200 with valid setup token when not installed', function () {
        $tokenData = app(GenerateSetupTokenAction::class)->execute();

        $this->get(route('setup', ['setup_token' => $tokenData['plaintext']]))
            ->assertStatus(200);
    });

    it('returns 404 when already installed without authorized session', function () {
        Setup::truncate();
        Setup::create(['is_installed' => true, 'completed_steps' => []]);

        $this->get(route('setup'))
            ->assertStatus(404);
    });
});
