<?php

declare(strict_types=1);

namespace Tests\Feature\Setup\Actions;

use App\Academics\School\Models\School;
use App\Core\Contracts\SendsNotifications;
use App\Program\Internship\Actions\CreateInternshipAction;
use App\Setup\Actions\FinalizeSetupAction;
use App\Setup\Actions\SetupDepartmentAction;
use App\Setup\Actions\SetupSchoolAction;
use App\Setup\Models\Setup;
use App\SysAdmin\Account\Actions\SaveRecoveryKeyAction;
use App\User\SuperAdmin\Actions\SetupSuperAdminAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use RuntimeException;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Setup::truncate();
});

test('finalize setup action successfully sets up school, department, admin, and saves recovery key', function () {
    // 1. Setup DB dependencies
    Role::create(['name' => 'superadmin']);

    // 2. Mock recovery key saving and notifications to isolate logic
    $saveRecoveryKeyMock = Mockery::mock(SaveRecoveryKeyAction::class);
    $saveRecoveryKeyMock->shouldReceive('execute')->once()->andReturn('/path/to/key');

    $sendNotificationMock = Mockery::mock(SendsNotifications::class);
    $sendNotificationMock->shouldReceive('execute')->once();

    // 3. Instantiate other actions (we can use real instances for school, dept, and superadmin)
    $setupSchool = app(SetupSchoolAction::class);
    $setupDept = app(SetupDepartmentAction::class);

    $setupAdmin = app(SetupSuperAdminAction::class);
    $createInternship = app(CreateInternshipAction::class);

    $finalizeAction = new FinalizeSetupAction(
        $setupSchool,
        $setupDept,
        $setupAdmin,
        $createInternship,
        $sendNotificationMock,
        $saveRecoveryKeyMock
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
    expect(Setup::count())->toBe(1);

    $setup = Setup::latest('created_at')->first();
    expect($setup->is_installed)->toBeTrue();
    expect($setup->school_id)->not->toBeNull();
    expect($setup->department_id)->not->toBeNull();
});

test('finalize setup action throws exception if system is already installed', function () {
    // Create an already installed setup configuration
    Setup::factory()->create([
        'is_installed' => true,
    ]);

    $finalizeAction = app(FinalizeSetupAction::class);

    expect(fn () => $finalizeAction->execute([], [], []))->toThrow(RuntimeException::class, 'System is already installed');
});
