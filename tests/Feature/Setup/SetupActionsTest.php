<?php

declare(strict_types=1);

use App\Domain\Auth\Enums\Role;
use App\Domain\School\Models\Department;
use App\Domain\School\Models\School;
use App\Domain\Setup\Actions\FinalizeSetupAction;
use App\Domain\Setup\Actions\GenerateSetupTokenAction;
use App\Domain\Setup\Actions\InitializeSuperAdminAction;
use App\Domain\Setup\Actions\RecoverSuperAdminAction;
use App\Domain\Setup\Actions\SetupDepartmentAction;
use App\Domain\Setup\Actions\SetupSchoolAction;
use App\Domain\Setup\Actions\SetupSuperAdminAction;
use App\Domain\Setup\Actions\ValidateSetupTokenAction;
use App\Domain\Setup\Models\Setup;
use App\Domain\User\Models\User;
use Spatie\Permission\Models\Role as RoleModel;

beforeEach(function () {
    RoleModel::create(['name' => Role::SUPER_ADMIN->value, 'guard_name' => 'web']);
    RoleModel::create(['name' => Role::ADMIN->value, 'guard_name' => 'web']);
    School::truncate();
});

describe('SetupSuperAdminAction', function () {
    it('creates or updates a super admin user', function () {
        $user = app(SetupSuperAdminAction::class)->execute([
            'name' => 'Super Admin',
            'username' => 'superadmin',
            'email' => 'admin@example.com',
            'password' => 'Admin123!',
        ]);

        expect($user)->toBeInstanceOf(User::class)
            ->and($user->hasRole(Role::SUPER_ADMIN->value))->toBeTrue()
            ->and($user->email_verified_at)->not->toBeNull();
    });
});

describe('SetupSchoolAction', function () {
    it('creates a school via the action', function () {
        $school = app(SetupSchoolAction::class)->execute([
            'name' => 'Test School',
            'institutional_code' => 'TS-'.str()->random(6),
            'address' => '123 Main St',
            'email' => 'school@test.com',
            'phone' => '021123456',
        ]);

        expect($school)->toBeInstanceOf(School::class)
            ->and($school->name)->toBe('Test School');
    });
});

describe('SetupDepartmentAction', function () {
    it('creates or updates a department', function () {
        $school = School::factory()->create();

        $department = app(SetupDepartmentAction::class)->execute($school->id, [
            'name' => 'Computer Science',
        ]);

        expect($department)->toBeInstanceOf(Department::class)
            ->and($department->name)->toBe('Computer Science')
            ->and($department->school_id)->toBe($school->id);
    });
});

describe('GenerateSetupTokenAction', function () {
    it('generates a setup token', function () {
        $result = app(GenerateSetupTokenAction::class)->execute();

        expect($result)->toHaveKeys(['plaintext', 'expires_at']);
        expect($result['plaintext'])->toBeString()->not->toBeEmpty();
    });
});

describe('ValidateSetupTokenAction', function () {
    it('validates a valid token', function () {
        $tokenResult = app(GenerateSetupTokenAction::class)->execute();

        expect(fn () => app(ValidateSetupTokenAction::class)->execute($tokenResult['plaintext']))
            ->not->toThrow(Exception::class);
    });

    it('throws for invalid token', function () {
        app(ValidateSetupTokenAction::class)->execute('invalid-token');
    })->throws(RuntimeException::class);
});

describe('InitializeSuperAdminAction', function () {
    it('creates a super admin user', function () {
        $user = app(InitializeSuperAdminAction::class)->execute(
            email: 'init@example.com',
            password: 'Secure1Pass',
            name: 'Init Admin',
        );

        expect($user)->toBeInstanceOf(User::class)
            ->and($user->hasRole(Role::SUPER_ADMIN->value))->toBeTrue()
            ->and($user->profile)->not->toBeNull();
    });
});

describe('FinalizeSetupAction', function () {
    it('completes full setup and returns recovery key', function () {
        Setup::truncate();
        Setup::create(['is_installed' => false, 'completed_steps' => []]);

        $recoveryKey = app(FinalizeSetupAction::class)->execute(
            schoolData: [
                'name' => 'Finalize School',
                'institutional_code' => 'FS-'.str()->random(6),
                'address' => '456 Oak St',
                'email' => 'finalize@test.com',
                'phone' => '021654321',
            ],
            departmentData: ['name' => 'Mathematics'],
            adminData: [
                'name' => 'Final Admin',
                'username' => 'finaladmin',
                'email' => 'finaladmin@test.com',
                'password' => 'Secure1Pass',
            ],
        );

        expect($recoveryKey)->toBeString()->not->toBeEmpty();

        $setup = Setup::first();
        expect($setup->is_installed)->toBeTrue()
            ->and($setup->setup_token)->toBeNull();
    });
});

describe('RecoverSuperAdminAction', function () {
    it('creates a new super admin for recovery', function () {
        $user = app(RecoverSuperAdminAction::class)->execute(
            email: 'recover@example.com',
            password: 'NewPass1!',
        );

        expect($user)->toBeInstanceOf(User::class)
            ->and($user->hasRole(Role::SUPER_ADMIN->value))->toBeTrue();
    });
});
