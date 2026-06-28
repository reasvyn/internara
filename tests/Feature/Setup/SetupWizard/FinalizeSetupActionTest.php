<?php

declare(strict_types=1);

namespace Tests\Feature\Setup\SetupWizard\Actions;

use App\Academics\Department\Models\Department;
use App\Core\Contracts\SendsNotifications;
use App\Settings\Services\Settings;
use App\Setup\Entities\SetupEntity;
use App\Setup\SetupWizard\Actions\FinalizeSetupAction;
use App\Setup\SetupWizard\Actions\SetupDepartmentAction;
use App\Setup\SetupWizard\Actions\SetupSchoolAction;
use App\Setup\SetupWizard\Actions\SetupSuperAdminAction;
use App\User\UserManagement\Actions\SaveRecoveryKeyAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use RuntimeException;

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

test(
    'finalize setup action successfully sets up school, department, admin, and saves recovery key',
    function () {

        $saveRecoveryKeyMock = Mockery::mock(SaveRecoveryKeyAction::class);
        $saveRecoveryKeyMock->shouldReceive('execute')->once()->andReturn('/path/to/key');

        $sendNotificationMock = Mockery::mock(SendsNotifications::class);
        $sendNotificationMock->shouldReceive('execute')->once();

        $setupSchool = app(SetupSchoolAction::class);
        $setupDept = app(SetupDepartmentAction::class);
        $setupAdmin = app(SetupSuperAdminAction::class);

        $finalizeAction = new FinalizeSetupAction(
            $setupSchool,
            $setupDept,
            $setupAdmin,
            $sendNotificationMock,
            $saveRecoveryKeyMock,
        );

        $schoolData = [
            'name' => 'Test School',
            'institutional_code' => 'SCH-TEST',
            'email' => 'school@test.com',
        ];

        $departmentData = [
            'name' => 'Computer Science',
        ];

        $adminData = [
            'email' => 'admin@internara.dev',
            'password' => 'Securepwd123',
        ];

        $recoveryKey = $finalizeAction->execute($schoolData, $departmentData, $adminData);

        expect($recoveryKey)->not->toBeEmpty();

        $state = SetupEntity::get();
        expect($state->isInstalled())->toBeTrue();

        expect(Department::where('name', 'Computer Science')->exists())->toBeTrue();
    },
);

test('finalize setup action throws exception if system is already installed', function () {
    Settings::set([
        'setup.is_installed' => ['value' => true, 'group' => 'setup', 'type' => 'boolean'],
    ]);

    $finalizeAction = app(FinalizeSetupAction::class);

    expect(fn () => $finalizeAction->execute([], [], []))->toThrow(
        RuntimeException::class,
        'System is already installed',
    );
});
