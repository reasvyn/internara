<?php

declare(strict_types=1);

namespace Tests\Feature\Setup\SetupWizard;

use App\Core\Contracts\SendsNotifications;
use App\Settings\Support\Settings;
use App\Setup\SetupWizard\Livewire\SetupWizard;
use App\SysAdmin\Account\Actions\SaveRecoveryKeyAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Mockery;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Settings::set([
        'setup.is_installed' => ['value' => false, 'group' => 'setup', 'type' => 'boolean'],
        'setup.install_token' => ['value' => null, 'group' => 'setup', 'type' => 'string'],
        'setup.token_expires_at' => ['value' => null, 'group' => 'setup', 'type' => 'datetime'],
        'setup.completed_steps' => ['value' => [], 'group' => 'setup', 'type' => 'json'],
        'setup.install_recovery_key' => ['value' => null, 'group' => 'setup', 'type' => 'string'],
        'setup.token_version' => ['value' => 0, 'group' => 'setup', 'type' => 'integer'],
    ]);
});

test('wizard mounts and executes audit successfully', function () {
    Livewire::test(SetupWizard::class)->assertSet('currentStep', 1)->assertSet('auditPassed', true);
});

test('wizard step 1 requires audit to pass before proceeding', function () {
    Livewire::test(SetupWizard::class)
        ->set('auditPassed', false)
        ->call('nextStep')
        ->assertSet('currentStep', 1);
});

test('wizard proceeds through all steps and completes setup', function () {
    Role::create(['name' => 'superadmin']);

    // Mock non-final dependencies of FinalizeSetupAction
    $saveRecoveryKeyMock = Mockery::mock(SaveRecoveryKeyAction::class);
    $saveRecoveryKeyMock->shouldReceive('execute')->once()->andReturn('mock_recovery_key_123');
    app()->instance(SaveRecoveryKeyAction::class, $saveRecoveryKeyMock);

    $sendNotificationMock = Mockery::mock(SendsNotifications::class);
    $sendNotificationMock->shouldReceive('execute')->once();
    app()->instance(SendsNotifications::class, $sendNotificationMock);

    $test = Livewire::test(SetupWizard::class)
        // Step 1: Welcome & Audit
        ->assertSet('currentStep', 1)
        ->call('nextStep')
        ->assertSet('currentStep', 2)

        // Step 2: Super Admin Form
        ->set('superAdminForm.email', 'superadmin@internara.dev')
        ->set('superAdminForm.password', 'SecurePassword123')
        ->set('superAdminForm.password_confirmation', 'SecurePassword123')
        ->call('nextStep')
        ->assertSet('currentStep', 3)

        // Step 3: School Profile Form
        ->set('schoolForm.name', 'SMK Negeri 1 Test')
        ->set('schoolForm.institutional_code', '12345678')
        ->set('schoolForm.email', 'school@test.sch.id')
        ->call('nextStep')
        ->assertSet('currentStep', 4)

        // Step 4: Department Form
        ->set('departmentForm.name', 'Rekayasa Perangkat Lunak')
        ->set('departmentForm.description', 'Software engineering major')
        ->call('nextStep')
        ->assertSet('currentStep', 5)

        // Step 5: Internship Program Form (Optional)
        ->set('internshipForm.name', 'PKL Semester Ganjil 2026')
        ->set('internshipForm.start_date', '2026-07-01')
        ->set('internshipForm.end_date', '2026-12-31')
        ->call('nextStep')
        ->assertSet('currentStep', 6)

        // Step 6: Finalization & Recovery Code Key
        ->set('dataVerified', true)
        ->set('securityAware', true)
        ->call('finish')

        // Step 7: Completed
        ->assertSet('currentStep', 7);

    expect(strlen($test->get('recoveryKey')))->toBe(64);
});
