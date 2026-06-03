<?php

declare(strict_types=1);

namespace Tests\Feature\Setup\Livewire;

use App\Domain\Core\Data\AuditCheck;
use App\Domain\Core\Data\AuditReport;
use App\Domain\Core\Enums\AuditCategory;
use App\Domain\Core\Enums\AuditStatus;
use App\Domain\Setup\Livewire\SetupWizard;
use App\Domain\Setup\Models\Setup;
use App\Domain\Setup\Services\EnvironmentAuditor;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Setup::query()->delete();
    session()->flush();
    $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
});

function navigateTo(int $targetStep)
{
    $component = Livewire::test(SetupWizard::class);

    if ($targetStep === 1) {
        return $component;
    }

    $component->call('nextStep');

    if ($targetStep === 2) {
        return $component;
    }

    $component
        ->set('schoolForm.name', 'Test School')
        ->set('schoolForm.institutional_code', 'SCH001')
        ->set('schoolForm.email', 'school@example.com')
        ->call('nextStep');

    if ($targetStep === 3) {
        return $component;
    }

    $component
        ->set('departmentForm.name', 'Computer Science')
        ->call('nextStep');

    if ($targetStep === 4) {
        return $component;
    }

    $component
        ->set('adminForm.email', 'admin@example.com')
        ->set('adminForm.password', 'Admin123456')
        ->set('adminForm.password_confirmation', 'Admin123456')
        ->call('nextStep');

    if ($targetStep === 5) {
        return $component;
    }

    $component->call('nextStep');

    return $component;
}

describe('SetupWizard', function () {
    // ─── MOUNT & INITIAL STATE ───────────────────────────────────

    it('mounts at step 1 when not installed', function () {
        Livewire::test(SetupWizard::class)
            ->assertSet('currentStep', 1);
    });

    it('redirects to login when installed without completed session', function () {
        Setup::factory()->installed()->create();

        Livewire::test(SetupWizard::class)
            ->assertRedirect(route('login'));
    });

    it('shows step 7 when already installed with completed session', function () {
        Setup::factory()->installed()->create();
        session()->put('setup.completed', true);

        Livewire::test(SetupWizard::class)
            ->assertSet('currentStep', 7);
    });

    it('initializes admin name and username from config defaults', function () {
        Livewire::test(SetupWizard::class)
            ->assertSet('adminForm.name', config('setup.defaults.admin_name'))
            ->assertSet('adminForm.username', config('setup.defaults.admin_username'));
    });

    it('runs environment audit on mount', function () {
        Livewire::test(SetupWizard::class)
            ->assertSet('auditPassed', true);
    });

    it('restores form state from session on mount', function () {
        session()->put('setup.form_data', [
            'school' => ['name' => 'Restored School', 'institutional_code' => 'R001', 'email' => 'r@test.com'],
            'department' => ['name' => 'Restored Dept'],
            'admin' => ['name' => 'Admin', 'username' => 'admin', 'email' => 'admin@test.com'],
            'internship' => ['name' => 'Restored Internship'],
        ]);

        Livewire::test(SetupWizard::class)
            ->assertSet('schoolForm.name', 'Restored School')
            ->assertSet('departmentForm.name', 'Restored Dept')
            ->assertSet('adminForm.email', 'admin@test.com')
            ->assertSet('internshipForm.name', 'Restored Internship');
    });

    it('restores partial form state gracefully', function () {
        session()->put('setup.form_data', [
            'school' => ['name' => 'Partial School'],
        ]);

        Livewire::test(SetupWizard::class)
            ->assertSet('schoolForm.name', 'Partial School')
            ->assertSet('schoolForm.email', '');
    });

    it('restores state with only admin data', function () {
        session()->put('setup.form_data', [
            'admin' => ['name' => 'Custom Admin', 'username' => 'custom', 'email' => 'custom@test.com'],
        ]);

        Livewire::test(SetupWizard::class)
            ->assertSet('adminForm.name', 'Custom Admin')
            ->assertSet('adminForm.username', 'custom')
            ->assertSet('adminForm.email', 'custom@test.com');
    });

    // ─── SECURITY & PRIVACY ──────────────────────────────────────

    it('does not persist password to session on form update', function () {
        Livewire::test(SetupWizard::class)
            ->set('adminForm.email', 'admin@example.com')
            ->set('adminForm.password', 'Secret123!')
            ->set('adminForm.password_confirmation', 'Secret123!');

        $adminSession = session('setup.form_data.admin');
        expect($adminSession)->toHaveKey('email');
        expect($adminSession)->not->toHaveKey('password');
        expect($adminSession)->not->toHaveKey('password_confirmation');
    });

    it('does not store recovery key in session after finish', function () {
        navigateTo(6)
            ->set('dataVerified', true)
            ->set('securityAware', true)
            ->call('finish');

        expect(session()->has('setup.recovery_key'))->toBeFalse();
        expect(session()->has('recoveryKey'))->toBeFalse();
    });

    it('recovery key is only available as component property after finish', function () {
        $component = navigateTo(6)
            ->set('dataVerified', true)
            ->set('securityAware', true)
            ->call('finish');

        $key = $component->get('recoveryKey');
        expect($key)->toBeString();
        expect(strlen($key))->toBe(64);
    });

    // ─── AUDIT ───────────────────────────────────────────────────

    it('blocks next step from welcome when audit fails', function () {
        $auditor = \Mockery::mock(EnvironmentAuditor::class);
        $auditor->shouldReceive('audit')->andReturn(new AuditReport([
            new AuditCheck(AuditCategory::REQUIREMENTS, 'php_version', AuditStatus::FAIL, 'php_version_fail', ['required' => '9.0'], ['current' => '8.4']),
        ]));
        $this->app->instance(EnvironmentAuditor::class, $auditor);

        Livewire::test(SetupWizard::class)
            ->call('nextStep')
            ->assertSet('currentStep', 1);
    });

    it('re-runs audit when environment auditor changes', function () {
        $auditor = \Mockery::mock(EnvironmentAuditor::class);
        $auditor->shouldReceive('audit')->andReturn(new AuditReport([
            new AuditCheck(AuditCategory::REQUIREMENTS, 'php_version', AuditStatus::PASS, 'php_version_pass', ['required' => '8.4'], ['current' => '8.4']),
        ]));
        $this->app->instance(EnvironmentAuditor::class, $auditor);

        Livewire::test(SetupWizard::class)
            ->call('runAudit')
            ->assertSet('auditPassed', true);
    });

    it('produces structured audit data with categories', function () {
        $auditor = \Mockery::mock(EnvironmentAuditor::class);
        $auditor->shouldReceive('audit')->andReturn(new AuditReport([
            new AuditCheck(AuditCategory::REQUIREMENTS, 'php_version', AuditStatus::PASS, 'php_version_pass', ['required' => '8.4'], ['current' => '8.4']),
            new AuditCheck(AuditCategory::DATABASE, 'db_connection', AuditStatus::PASS, 'db_connection_pass', [], []),
        ]));
        $this->app->instance(EnvironmentAuditor::class, $auditor);

        $component = Livewire::test(SetupWizard::class);
        $audit = $component->get('audit');

        expect($audit)->toHaveKey('categories');
        $categories = $audit['categories'];

        expect($categories)->toHaveKey(AuditCategory::REQUIREMENTS->value);
        expect($categories)->toHaveKey(AuditCategory::DATABASE->value);

        $reqCategory = $categories[AuditCategory::REQUIREMENTS->value];
        expect($reqCategory)->toHaveKey('label');
        expect($reqCategory)->toHaveKey('checks');
        expect($reqCategory['checks'])->toBeArray();
        expect($reqCategory['checks'][0]['name'])->toBe('php_version');
        expect($reqCategory['checks'][0]['status'])->toBe('pass');
    });

    it('audit structure shows fail status for failing checks', function () {
        $auditor = \Mockery::mock(EnvironmentAuditor::class);
        $auditor->shouldReceive('audit')->andReturn(new AuditReport([
            new AuditCheck(AuditCategory::REQUIREMENTS, 'php_version', AuditStatus::PASS, 'php_version_pass', ['required' => '8.4'], ['current' => '8.4']),
            new AuditCheck(AuditCategory::PERMISSIONS, 'storage_writable', AuditStatus::FAIL, 'storage_not_writable', ['path' => 'storage'], []),
        ]));
        $this->app->instance(EnvironmentAuditor::class, $auditor);

        $component = Livewire::test(SetupWizard::class);
        $audit = $component->get('audit');
        $permChecks = $audit['categories'][AuditCategory::PERMISSIONS->value]['checks'];

        expect($permChecks[0]['status'])->toBe('fail');
        expect($component->get('auditPassed'))->toBeFalse();
    });

    it('audit handles empty check list gracefully', function () {
        $auditor = \Mockery::mock(EnvironmentAuditor::class);
        $auditor->shouldReceive('audit')->andReturn(new AuditReport([]));
        $this->app->instance(EnvironmentAuditor::class, $auditor);

        $component = Livewire::test(SetupWizard::class);

        expect($component->get('audit'))->toBe(['categories' => []]);
        expect($component->get('auditPassed'))->toBeTrue();
    });

    // ─── VIEW & RENDERING ────────────────────────────────────────

    it('renders with app name in the view', function () {
        Livewire::test(SetupWizard::class)
            ->assertSee(config('app.name'));
    });

    it('title returns localized string with app name', function () {
        $component = Livewire::test(SetupWizard::class);

        expect($component->instance()->title())->toBeString();
        expect($component->instance()->title())->toContain(config('app.name'));
    });

    // ─── STEP NAVIGATION ─────────────────────────────────────────

    it('advances from step 1 to 2 when audit passes', function () {
        Livewire::test(SetupWizard::class)
            ->call('nextStep')
            ->assertSet('currentStep', 2);
    });

    it('goes back to previous step', function () {
        Livewire::test(SetupWizard::class)
            ->call('nextStep')
            ->call('prevStep')
            ->assertSet('currentStep', 1);
    });

    it('does not go below step 1', function () {
        Livewire::test(SetupWizard::class)
            ->call('prevStep')
            ->assertSet('currentStep', 1);
    });

    it('goes to a step by key when it is completed', function () {
        Setup::factory()->create([
            'completed_steps' => ['school'],
            'is_installed' => false,
        ]);

        Livewire::test(SetupWizard::class)
            ->call('nextStep')
            ->assertSet('currentStep', 2)
            ->call('goToStep', 'school')
            ->assertSet('currentStep', 2);
    });

    it('does not navigate to an invalid step key', function () {
        Livewire::test(SetupWizard::class)
            ->call('goToStep', 'nonexistent')
            ->assertSet('currentStep', 1);
    });

    it('does not skip forward to uncompleted steps via goToStep', function () {
        Livewire::test(SetupWizard::class)
            ->call('goToStep', 'school')
            ->assertSet('currentStep', 1);
    });

    it('goes back to previous step from each boundary', function () {
        $component = Livewire::test(SetupWizard::class);

        $component->call('nextStep');
        expect($component->get('currentStep'))->toBe(2);
        $component->call('prevStep');
        expect($component->get('currentStep'))->toBe(1);

        $component->call('nextStep');
        $component
            ->set('schoolForm.name', 'S')
            ->set('schoolForm.institutional_code', 'C')
            ->set('schoolForm.email', 's@t.com')
            ->call('nextStep');
        expect($component->get('currentStep'))->toBe(3);
        $component->call('prevStep');
        expect($component->get('currentStep'))->toBe(2);

        $component
            ->set('schoolForm.name', 'S')
            ->set('schoolForm.institutional_code', 'C')
            ->set('schoolForm.email', 's@t.com')
            ->call('nextStep');
        $component->set('departmentForm.name', 'D')->call('nextStep');
        expect($component->get('currentStep'))->toBe(4);
        $component->call('prevStep');
        expect($component->get('currentStep'))->toBe(3);
    });

    it('goToStep with same current step does nothing', function () {
        Livewire::test(SetupWizard::class)
            ->call('goToStep', 'welcome')
            ->assertSet('currentStep', 1);
    });

    it('goToStep navigates back to completed steps', function () {
        Setup::factory()->create([
            'completed_steps' => ['school', 'department', 'account'],
            'is_installed' => false,
        ]);

        $component = Livewire::test(SetupWizard::class)
            ->call('nextStep')
            ->assertSet('currentStep', 2);

        $component->call('goToStep', 'school')->assertSet('currentStep', 2);
        $component
            ->set('schoolForm.name', 'S')
            ->set('schoolForm.institutional_code', 'C')
            ->set('schoolForm.email', 's@t.com')
            ->call('nextStep')
            ->assertSet('currentStep', 3);
        $component->call('goToStep', 'department')->assertSet('currentStep', 3);
    });

    it('goToStep with completed step returns to it regardless of current step', function () {
        Setup::factory()->create([
            'completed_steps' => ['school', 'department', 'account'],
            'is_installed' => false,
        ]);

        $component = navigateTo(4);
        $component->call('goToStep', 'school')->assertSet('currentStep', 2);
    });

    // ─── SCHOOL FORM (STEP 2) ────────────────────────────────────

    it('stays on step 2 when school name is empty', function () {
        Livewire::test(SetupWizard::class)
            ->call('nextStep')
            ->call('nextStep')
            ->assertSet('currentStep', 2)
            ->assertHasErrors(['schoolForm.name']);
    });

    it('advances to step 3 when school form is valid', function () {
        Livewire::test(SetupWizard::class)
            ->call('nextStep')
            ->set('schoolForm.name', 'Test School')
            ->set('schoolForm.institutional_code', 'SCH001')
            ->set('schoolForm.email', 'school@example.com')
            ->call('nextStep')
            ->assertSet('currentStep', 3);
    });

    it('requires school institutional_code', function () {
        Livewire::test(SetupWizard::class)
            ->call('nextStep')
            ->set('schoolForm.name', 'Test School')
            ->set('schoolForm.email', 'school@example.com')
            ->call('nextStep')
            ->assertSet('currentStep', 2)
            ->assertHasErrors(['schoolForm.institutional_code']);
    });

    it('rejects invalid school email format', function () {
        Livewire::test(SetupWizard::class)
            ->call('nextStep')
            ->set('schoolForm.name', 'Test School')
            ->set('schoolForm.institutional_code', 'SCH001')
            ->set('schoolForm.email', 'not-an-email')
            ->call('nextStep')
            ->assertSet('currentStep', 2)
            ->assertHasErrors(['schoolForm.email']);
    });

    it('accepts optional school fields without validation errors', function () {
        Livewire::test(SetupWizard::class)
            ->call('nextStep')
            ->set('schoolForm.name', 'Test School')
            ->set('schoolForm.institutional_code', 'SCH001')
            ->set('schoolForm.email', 'school@example.com')
            ->set('schoolForm.address', 'Jl. Pendidikan No. 1')
            ->set('schoolForm.phone', '021-123456')
            ->set('schoolForm.website', 'https://school.example.com')
            ->set('schoolForm.principal_name', 'Dr. Principal')
            ->call('nextStep')
            ->assertSet('currentStep', 3);
    });

    it('rejects invalid school website url', function () {
        Livewire::test(SetupWizard::class)
            ->call('nextStep')
            ->set('schoolForm.name', 'Test School')
            ->set('schoolForm.institutional_code', 'SCH001')
            ->set('schoolForm.email', 'school@example.com')
            ->set('schoolForm.website', 'not-a-url')
            ->call('nextStep')
            ->assertSet('currentStep', 2)
            ->assertHasErrors(['schoolForm.website']);
    });

    it('rejects school name exceeding max length', function () {
        Livewire::test(SetupWizard::class)
            ->call('nextStep')
            ->set('schoolForm.name', str_repeat('A', 256))
            ->set('schoolForm.institutional_code', 'SCH001')
            ->set('schoolForm.email', 'school@example.com')
            ->call('nextStep')
            ->assertSet('currentStep', 2)
            ->assertHasErrors(['schoolForm.name']);
    });

    // ─── DEPARTMENT FORM (STEP 3) ────────────────────────────────

    it('stays on step 3 when department name is empty', function () {
        navigateTo(3)
            ->call('nextStep')
            ->assertSet('currentStep', 3)
            ->assertHasErrors(['departmentForm.name']);
    });

    it('advances to step 4 when department form is valid', function () {
        navigateTo(3)
            ->set('departmentForm.name', 'Computer Science')
            ->call('nextStep')
            ->assertSet('currentStep', 4);
    });

    it('accepts optional department description', function () {
        navigateTo(3)
            ->set('departmentForm.name', 'Teknik Mesin')
            ->set('departmentForm.description', 'Program keahlian teknik mesin')
            ->call('nextStep')
            ->assertSet('currentStep', 4);
    });

    // ─── ADMIN FORM (STEP 4) ─────────────────────────────────────

    it('stays on step 4 when admin email is empty', function () {
        navigateTo(4)
            ->call('nextStep')
            ->assertSet('currentStep', 4)
            ->assertHasErrors(['adminForm.email']);
    });

    it('stays on step 4 when password confirmation does not match', function () {
        navigateTo(4)
            ->set('adminForm.email', 'admin@example.com')
            ->set('adminForm.password', 'Admin123456')
            ->set('adminForm.password_confirmation', 'DifferentPass1')
            ->call('nextStep')
            ->assertSet('currentStep', 4)
            ->assertHasErrors(['adminForm.password']);
    });

    it('requires password with mixed case and numbers', function () {
        navigateTo(4)
            ->set('adminForm.email', 'admin@example.com')
            ->set('adminForm.password', 'weak')
            ->set('adminForm.password_confirmation', 'weak')
            ->call('nextStep')
            ->assertSet('currentStep', 4)
            ->assertHasErrors(['adminForm.password']);
    });

    it('rejects invalid admin email format', function () {
        navigateTo(4)
            ->set('adminForm.email', 'not-valid-email')
            ->set('adminForm.password', 'Admin123456')
            ->set('adminForm.password_confirmation', 'Admin123456')
            ->call('nextStep')
            ->assertSet('currentStep', 4)
            ->assertHasErrors(['adminForm.email']);
    });

    it('advances to step 5 when admin form is valid', function () {
        navigateTo(4)
            ->set('adminForm.email', 'admin@example.com')
            ->set('adminForm.password', 'Admin123456')
            ->set('adminForm.password_confirmation', 'Admin123456')
            ->call('nextStep')
            ->assertSet('currentStep', 5);
    });

    it('requires password minimum 8 characters', function () {
        navigateTo(4)
            ->set('adminForm.email', 'admin@example.com')
            ->set('adminForm.password', 'Ab1')
            ->set('adminForm.password_confirmation', 'Ab1')
            ->call('nextStep')
            ->assertSet('currentStep', 4)
            ->assertHasErrors(['adminForm.password']);
    });

    // ─── INTERNSHIP FORM (STEP 5) ────────────────────────────────

    it('advances to step 6 when internship is skipped (empty)', function () {
        navigateTo(5)
            ->call('nextStep')
            ->assertSet('currentStep', 6);
    });

    it('stays on step 5 when internship name is set but dates are missing', function () {
        navigateTo(5)
            ->set('internshipForm.name', 'Summer Internship')
            ->call('nextStep')
            ->assertSet('currentStep', 5)
            ->assertHasErrors(['internshipForm.start_date']);
    });

    it('advances to step 6 when internship form is fully valid', function () {
        navigateTo(5)
            ->set('internshipForm.name', 'Summer Internship')
            ->set('internshipForm.start_date', '2026-07-01')
            ->set('internshipForm.end_date', '2026-09-30')
            ->call('nextStep')
            ->assertSet('currentStep', 6);
    });

    it('validates end date is after start date', function () {
        navigateTo(5)
            ->set('internshipForm.name', 'Internship')
            ->set('internshipForm.start_date', '2026-09-30')
            ->set('internshipForm.end_date', '2026-07-01')
            ->call('nextStep')
            ->assertSet('currentStep', 5)
            ->assertHasErrors(['internshipForm.end_date']);
    });

    it('triggers validation when start_date is set without name', function () {
        navigateTo(5)
            ->set('internshipForm.start_date', '2026-07-01')
            ->set('internshipForm.end_date', '2026-09-30')
            ->call('nextStep')
            ->assertSet('currentStep', 5)
            ->assertHasErrors(['internshipForm.name']);
    });

    it('skips validation when only description is set', function () {
        navigateTo(5)
            ->set('internshipForm.description', 'Some description')
            ->call('nextStep')
            ->assertSet('currentStep', 6);
    });

    // ─── STATE PERSISTENCE ───────────────────────────────────────

    it('persists school form data to session on update', function () {
        Livewire::test(SetupWizard::class)
            ->set('schoolForm.name', 'Session School');

        expect(session('setup.form_data.school.name'))->toBe('Session School');
    });

    it('persists all form types to session', function () {
        Livewire::test(SetupWizard::class)
            ->set('schoolForm.name', 'S')
            ->set('departmentForm.name', 'D')
            ->set('adminForm.email', 'a@b.com')
            ->set('internshipForm.name', 'I');

        expect(session('setup.form_data.school.name'))->toBe('S');
        expect(session('setup.form_data.department.name'))->toBe('D');
        expect(session('setup.form_data.admin.email'))->toBe('a@b.com');
        expect(session('setup.form_data.internship.name'))->toBe('I');
    });

    it('does not persist non-form property changes to session', function () {
        Livewire::test(SetupWizard::class)
            ->set('showGuide', true);

        expect(session()->has('setup.form_data'))->toBeFalse();
    });

    it('persists school form data with all fields to session', function () {
        Livewire::test(SetupWizard::class)
            ->set('schoolForm.name', 'Full School')
            ->set('schoolForm.institutional_code', 'FULL')
            ->set('schoolForm.email', 'full@test.com')
            ->set('schoolForm.address', 'Jl. Test')
            ->set('schoolForm.phone', '021-999')
            ->set('schoolForm.website', 'https://full.sch.id')
            ->set('schoolForm.principal_name', 'Kepala Sekolah');

        $school = session('setup.form_data.school');
        expect($school['name'])->toBe('Full School');
        expect($school['institutional_code'])->toBe('FULL');
        expect($school['email'])->toBe('full@test.com');
        expect($school['address'])->toBe('Jl. Test');
        expect($school['phone'])->toBe('021-999');
        expect($school['website'])->toBe('https://full.sch.id');
        expect($school['principal_name'])->toBe('Kepala Sekolah');
    });

    // ─── GUIDE MODAL ─────────────────────────────────────────────

    it('toggles guide modal', function () {
        Livewire::test(SetupWizard::class)
            ->assertSet('showGuide', false)
            ->set('showGuide', true)
            ->assertSet('showGuide', true);
    });

    // ─── FINISH (STEP 6 → 7) ─────────────────────────────────────

    it('requires both checkboxes before finishing', function () {
        navigateTo(6)
            ->call('finish')
            ->assertHasErrors(['dataVerified', 'securityAware']);
    });

    it('requires dataVerified checkbox', function () {
        navigateTo(6)
            ->set('securityAware', true)
            ->call('finish')
            ->assertHasErrors(['dataVerified']);
    });

    it('requires securityAware checkbox', function () {
        navigateTo(6)
            ->set('dataVerified', true)
            ->call('finish')
            ->assertHasErrors(['securityAware']);
    });

    it('advances to step 7 after successful finish', function () {
        navigateTo(6)
            ->set('dataVerified', true)
            ->set('securityAware', true)
            ->call('finish')
            ->assertSet('currentStep', 7);
    });

    it('stores recovery key string after finish', function () {
        $component = navigateTo(6);
        $component
            ->set('dataVerified', true)
            ->set('securityAware', true)
            ->call('finish');

        expect($component->get('recoveryKey'))->toBeString()->not->toBeEmpty();
    });

    it('marks setup as completed in session after finish', function () {
        navigateTo(6)
            ->set('dataVerified', true)
            ->set('securityAware', true)
            ->call('finish');

        expect(session('setup.completed'))->toBeTrue();
    });

    it('creates school record on finish', function () {
        navigateTo(6)
            ->set('dataVerified', true)
            ->set('securityAware', true)
            ->call('finish');

        $this->assertDatabaseHas('schools', ['name' => 'Test School']);
    });

    it('creates department record on finish', function () {
        navigateTo(6)
            ->set('dataVerified', true)
            ->set('securityAware', true)
            ->call('finish');

        $this->assertDatabaseHas('departments', ['name' => 'Computer Science']);
    });

    it('creates admin user on finish', function () {
        navigateTo(6)
            ->set('dataVerified', true)
            ->set('securityAware', true)
            ->call('finish');

        $this->assertDatabaseHas('users', ['email' => 'admin@example.com']);
    });

    it('gives admin user super_admin role on finish', function () {
        navigateTo(6)
            ->set('dataVerified', true)
            ->set('securityAware', true)
            ->call('finish');

        $user = User::where('email', 'admin@example.com')->first();
        expect($user->hasRole('super_admin'))->toBeTrue();
    });

    it('marks setup as installed after finish', function () {
        navigateTo(6)
            ->set('dataVerified', true)
            ->set('securityAware', true)
            ->call('finish');

        $setup = Setup::first();
        expect($setup->is_installed)->toBeTrue();
    });

    it('clears session form data after finish', function () {
        navigateTo(6)
            ->set('dataVerified', true)
            ->set('securityAware', true)
            ->call('finish');

        expect(session()->has('setup.form_data'))->toBeFalse();
    });

    it('clears setup authorized and token session keys after finish', function () {
        navigateTo(6)
            ->set('dataVerified', true)
            ->set('securityAware', true)
            ->call('finish');

        expect(session()->has('setup.authorized'))->toBeFalse();
        expect(session()->has('setup.token'))->toBeFalse();
    });

    it('creates school with complete address data on finish', function () {
        $component = Livewire::test(SetupWizard::class);
        $component->call('nextStep');
        $component
            ->set('schoolForm.name', 'SMK Negeri 1 Jakarta')
            ->set('schoolForm.institutional_code', 'SMKN1JKT')
            ->set('schoolForm.email', 'info@smkn1jkt.sch.id')
            ->set('schoolForm.address', 'Jl. Budi Utomo No. 7')
            ->set('schoolForm.phone', '021-3500001')
            ->set('schoolForm.website', 'https://smkn1jkt.sch.id')
            ->set('schoolForm.principal_name', 'Drs. Suharto, M.Pd.')
            ->call('nextStep');
        $component->set('departmentForm.name', 'Rekayasa Perangkat Lunak')->call('nextStep');
        $component
            ->set('adminForm.email', 'admin@smkn1jkt.sch.id')
            ->set('adminForm.password', 'SuperSecure2026!')
            ->set('adminForm.password_confirmation', 'SuperSecure2026!')
            ->call('nextStep');
        $component->call('nextStep');
        $component
            ->set('dataVerified', true)
            ->set('securityAware', true)
            ->call('finish');

        $this->assertDatabaseHas('schools', [
            'name' => 'SMK Negeri 1 Jakarta',
            'institutional_code' => 'SMKN1JKT',
            'email' => 'info@smkn1jkt.sch.id',
            'address' => 'Jl. Budi Utomo No. 7',
            'phone' => '021-3500001',
            'website' => 'https://smkn1jkt.sch.id',
            'principal_name' => 'Drs. Suharto, M.Pd.',
        ]);
    });

    it('admin user has verified email after finish', function () {
        navigateTo(6)
            ->set('dataVerified', true)
            ->set('securityAware', true)
            ->call('finish');

        $user = User::where('email', 'admin@example.com')->first();
        expect($user->hasVerifiedEmail())->toBeTrue();
    });

    // ─── FINISH WITH INTERNSHIP ──────────────────────────────────

    it('creates internship record when internship form is fully filled', function () {
        $component = Livewire::test(SetupWizard::class);

        $component->call('nextStep');
        $component
            ->set('schoolForm.name', 'Test School')
            ->set('schoolForm.institutional_code', 'SCH001')
            ->set('schoolForm.email', 'school@example.com')
            ->call('nextStep');
        $component
            ->set('departmentForm.name', 'Computer Science')
            ->call('nextStep');
        $component
            ->set('adminForm.email', 'admin@example.com')
            ->set('adminForm.password', 'Admin123456')
            ->set('adminForm.password_confirmation', 'Admin123456')
            ->call('nextStep');
        $component
            ->set('internshipForm.name', 'Summer Internship')
            ->set('internshipForm.start_date', '2026-07-01')
            ->set('internshipForm.end_date', '2026-09-30')
            ->call('nextStep');
        $component
            ->set('dataVerified', true)
            ->set('securityAware', true)
            ->call('finish');

        $this->assertDatabaseHas('internships', ['name' => 'Summer Internship']);
    });

    it('does not create internship record when internship is skipped', function () {
        navigateTo(6)
            ->set('dataVerified', true)
            ->set('securityAware', true)
            ->call('finish');

        $this->assertDatabaseMissing('internships', ['name' => 'Summer Internship']);
    });

    it('creates internship with description when provided', function () {
        $component = Livewire::test(SetupWizard::class);

        $component->call('nextStep');
        $component
            ->set('schoolForm.name', 'Test School')
            ->set('schoolForm.institutional_code', 'SCH001')
            ->set('schoolForm.email', 'school@example.com')
            ->call('nextStep');
        $component->set('departmentForm.name', 'Computer Science')->call('nextStep');
        $component
            ->set('adminForm.email', 'admin@example.com')
            ->set('adminForm.password', 'Admin123456')
            ->set('adminForm.password_confirmation', 'Admin123456')
            ->call('nextStep');
        $component
            ->set('internshipForm.name', 'Summer Internship')
            ->set('internshipForm.description', 'Magang musim panas untuk siswa kelas XI')
            ->set('internshipForm.start_date', '2026-07-01')
            ->set('internshipForm.end_date', '2026-09-30')
            ->call('nextStep');
        $component
            ->set('dataVerified', true)
            ->set('securityAware', true)
            ->call('finish');

        $this->assertDatabaseHas('internships', [
            'name' => 'Summer Internship',
            'description' => 'Magang musim panas untuk siswa kelas XI',
        ]);
    });

    it('does not create internship when internship step was skipped at step 5 but dates end up set', function () {
        $component = Livewire::test(SetupWizard::class);
        $component->call('nextStep');
        $component
            ->set('schoolForm.name', 'School A')
            ->set('schoolForm.institutional_code', 'SCH')
            ->set('schoolForm.email', 's@t.com')
            ->call('nextStep');
        $component->set('departmentForm.name', 'Dept')->call('nextStep');
        $component
            ->set('adminForm.email', 'a@b.com')
            ->set('adminForm.password', 'Admin123456')
            ->set('adminForm.password_confirmation', 'Admin123456')
            ->call('nextStep');
        $component
            ->set('internshipForm.name', '')
            ->set('internshipForm.start_date', '2026-07-01')
            ->set('internshipForm.end_date', '2026-09-30')
            ->call('nextStep');
        $component
            ->set('dataVerified', true)
            ->set('securityAware', true)
            ->call('finish');

        $this->assertDatabaseMissing('internships', ['start_date' => '2026-07-01']);
        $this->assertDatabaseHas('schools', ['name' => 'School A']);
    });

    // ─── EXCEPTION HANDLING ──────────────────────────────────────

    it('does not advance to step 7 when finish validation fails', function () {
        navigateTo(6)
            ->call('finish')
            ->assertSet('currentStep', 6);
    });

    it('finish does not create records when unchecked checkboxes', function () {
        navigateTo(6)
            ->call('finish');

        $this->assertDatabaseCount('schools', 0);
        $this->assertDatabaseCount('users', 0);
    });

    it('finish does not mark setup as installed when checkboxes unchecked', function () {
        navigateTo(6)
            ->call('finish');

        expect(Setup::count())->toBe(0);
    });

    // ─── FINISH SESSION ─────────────────────────────────────────

    it('finishSession clears completed flag and redirects to login', function () {
        session()->put('setup.completed', true);

        Livewire::test(SetupWizard::class)
            ->call('finishSession')
            ->assertRedirect(route('login'));

        expect(session()->has('setup.completed'))->toBeFalse();
    });

    // ─── EDGE CASES ──────────────────────────────────────────────

    it('mounts multiple times without side effects', function () {
        $first = Livewire::test(SetupWizard::class);
        expect($first->get('currentStep'))->toBe(1);

        $first->set('schoolForm.name', 'School A');
        expect(session('setup.form_data.school.name'))->toBe('School A');

        $second = Livewire::test(SetupWizard::class);
        expect($second->get('currentStep'))->toBe(1);
        expect($second->get('schoolForm.name'))->toBe('School A');
    });

    it('handles special characters in form fields', function () {
        Livewire::test(SetupWizard::class)
            ->call('nextStep')
            ->set('schoolForm.name', 'SMK Negeri 1 Cianjur (Cabang Dinamis)')
            ->set('schoolForm.institutional_code', 'SMKN1CJR')
            ->set('schoolForm.email', 'info@smkn1cianjur.sch.id')
            ->set('schoolForm.address', 'Jl. Raya Cianjur No. 1, RT.01/RW.02, Kec. Cianjur')
            ->call('nextStep')
            ->assertSet('currentStep', 3);
    });
});
