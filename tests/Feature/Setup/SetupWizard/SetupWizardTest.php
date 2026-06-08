<?php

declare(strict_types=1);

namespace Tests\Feature\Setup\SetupWizard;

use App\Core\Contracts\SendsNotifications;
use App\Settings\Support\Settings;
use App\Setup\SetupWizard\Livewire\SetupWizard;
use App\SysAdmin\UserManagement\Actions\SaveRecoveryKeyAction;
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

    $saveRecoveryKeyMock = Mockery::mock(SaveRecoveryKeyAction::class);
    $saveRecoveryKeyMock->shouldReceive('execute')->once()->andReturn('mock_recovery_key_123');
    app()->instance(SaveRecoveryKeyAction::class, $saveRecoveryKeyMock);

    $sendNotificationMock = Mockery::mock(SendsNotifications::class);
    $sendNotificationMock->shouldReceive('execute')->once();
    app()->instance(SendsNotifications::class, $sendNotificationMock);

    $test = Livewire::test(SetupWizard::class)
        ->assertSet('currentStep', 1)
        ->call('nextStep')
        ->assertSet('currentStep', 2)

        ->set('superAdminForm.email', 'superadmin@internara.dev')
        ->set('superAdminForm.password', 'SecurePassword123')
        ->set('superAdminForm.password_confirmation', 'SecurePassword123')
        ->call('nextStep')
        ->assertSet('currentStep', 3)

        ->set('schoolForm.name', 'SMK Negeri 1 Test')
        ->set('schoolForm.institutional_code', '12345678')
        ->set('schoolForm.email', 'school@test.sch.id')
        ->call('nextStep')
        ->assertSet('currentStep', 4)

        ->set('departmentForm.name', 'Rekayasa Perangkat Lunak')
        ->set('departmentForm.description', 'Software engineering major')
        ->call('nextStep')
        ->assertSet('currentStep', 5)

        ->set('dataVerified', true)
        ->set('securityAware', true)
        ->call('finish')

        ->assertSet('currentStep', 6);

    expect(strlen($test->get('recoveryKey')))->toBe(64);
});

test('goToStep does nothing for unknown step key', function () {
    Livewire::test(SetupWizard::class)
        ->call('goToStep', 'nonexistent')
        ->assertSet('currentStep', 1);
});

test('goToStep allows moving to completed step', function () {
    Livewire::test(SetupWizard::class)
        ->assertSet('currentStep', 1)
        ->call('goToStep', 'welcome')
        ->assertSet('currentStep', 1);
});
