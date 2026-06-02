<?php

declare(strict_types=1);

namespace Tests\Feature\Setup\Livewire;

use App\Domain\Core\Data\AuditCheck;
use App\Domain\Core\Data\AuditReport;
use App\Domain\Core\Enums\AuditCategory;
use App\Domain\Core\Enums\AuditStatus;
use App\Domain\Setup\Actions\FinalizeSetupAction;
use App\Domain\Setup\Livewire\SetupWizard;
use App\Domain\Setup\Models\Setup;
use App\Domain\Setup\Services\EnvironmentAuditor;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Setup::query()->delete();
    session()->flush();
});

function navigateTo(int $targetStep): \Livewire\Testing\TestableLivewire
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

    // ─── AUDIT RE-RUN ────────────────────────────────────────────

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
            ->assertSet('currentStep', 3)
            ->assertNoErrors();
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

    it('advances to step 5 when admin form is valid', function () {
        navigateTo(4)
            ->set('adminForm.email', 'admin@example.com')
            ->set('adminForm.password', 'Admin123456')
            ->set('adminForm.password_confirmation', 'Admin123456')
            ->call('nextStep')
            ->assertSet('currentStep', 5);
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

        $user = \App\Domain\User\Models\User::where('email', 'admin@example.com')->first();
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

    // ─── EXCEPTION HANDLING ──────────────────────────────────────

    it('handles RuntimeException during finish gracefully', function () {
        $mock = \Mockery::mock(FinalizeSetupAction::class);
        $mock->shouldReceive('execute')
            ->once()
            ->andThrow(new \RuntimeException('Database connection failed'));
        $this->app->instance(FinalizeSetupAction::class, $mock);

        navigateTo(6)
            ->set('dataVerified', true)
            ->set('securityAware', true)
            ->call('finish')
            ->assertSet('currentStep', 6);
    });

    it('handles generic Throwable during finish gracefully', function () {
        $mock = \Mockery::mock(FinalizeSetupAction::class);
        $mock->shouldReceive('execute')
            ->once()
            ->andThrow(new \Error('Out of memory'));
        $this->app->instance(FinalizeSetupAction::class, $mock);

        navigateTo(6)
            ->set('dataVerified', true)
            ->set('securityAware', true)
            ->call('finish')
            ->assertSet('currentStep', 6);
    });

    // ─── FINISH SESSION ─────────────────────────────────────────

    it('finishSession clears completed flag and redirects to login', function () {
        session()->put('setup.completed', true);

        Livewire::test(SetupWizard::class)
            ->call('finishSession')
            ->assertRedirect(route('login'));

        expect(session()->has('setup.completed'))->toBeFalse();
    });
});
