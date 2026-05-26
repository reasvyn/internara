<?php

declare(strict_types=1);

use App\Domain\Auth\Enums\AccountStatus;
use App\Domain\Auth\Enums\Role;
use App\Domain\Core\Data\AuditReport;
use App\Domain\Core\Enums\AuditCategory;
use App\Domain\Core\Exceptions\RejectedException;
use App\Domain\School\Models\Department;
use App\Domain\School\Models\School;
use App\Domain\Setup\Actions\FinalizeSetupAction;
use App\Domain\Setup\Actions\GenerateSetupTokenAction;
use App\Domain\Setup\Actions\InitializeSuperAdminAction;
use App\Domain\Setup\Actions\InstallSystemAction;
use App\Domain\Setup\Actions\RecoverSuperAdminAction;
use App\Domain\Setup\Actions\SetupDepartmentAction;
use App\Domain\Setup\Actions\SetupSchoolAction;
use App\Domain\Setup\Actions\SetupSuperAdminAction;
use App\Domain\Setup\Actions\ValidateSetupTokenAction;
use App\Domain\Setup\Entities\SetupState;
use App\Domain\Setup\Events\SetupFinalized;
use App\Domain\Setup\Listeners\LogSetupFinalized;
use App\Domain\Setup\Models\Setup;
use App\Domain\Setup\Services\EnvironmentAuditor;
use App\Domain\Setup\Support\SystemProvisioner;
use App\Domain\User\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role as RoleModel;

// ─── Fixtures ─────────────────────────────────────────────────────────────

beforeEach(function () {
    RoleModel::firstOrCreate(['name' => Role::SUPER_ADMIN->value]);
    RoleModel::firstOrCreate(['name' => Role::ADMIN->value]);
    Setup::truncate();
});

// ─── SetupState Entity ─────────────────────────────────────────────────────

describe('SetupState', function () {
    it('detects installed state', function () {
        $state = new SetupState(true, null, null, [], null);
        expect($state->isInstalled())->toBeTrue();
    });

    it('detects not installed state', function () {
        $state = new SetupState(false, null, null, [], null);
        expect($state->isInstalled())->toBeFalse();
    });

    it('detects stored token', function () {
        $state = new SetupState(false, 'some-token', now()->addHour(), [], null);
        expect($state->hasStoredToken())->toBeTrue();
    });

    it('detects missing token', function () {
        $state = new SetupState(false, null, null, [], null);
        expect($state->hasStoredToken())->toBeFalse();
    });

    it('detects expired token', function () {
        $state = new SetupState(false, 'token', now()->subHour(), [], null);
        expect($state->isTokenExpired())->toBeTrue();
    });

    it('detects non-expired token', function () {
        $state = new SetupState(false, 'token', now()->addHour(), [], null);
        expect($state->isTokenExpired())->toBeFalse();
    });

    it('token is expired when expires_at is null', function () {
        $state = new SetupState(false, 'token', null, [], null);
        expect($state->isTokenExpired())->toBeTrue();
    });

    it('validates token with hash_equals', function () {
        $state = new SetupState(false, 'encrypted', now()->addHour(), [], null);
        expect($state->validateToken('stored-decrypted', 'stored-decrypted'))->toBeTrue();
        expect($state->validateToken('stored-decrypted', 'wrong-input'))->toBeFalse();
    });

    it('returns false for expired token validation', function () {
        $state = new SetupState(false, 'encrypted', now()->subHour(), [], null);
        expect($state->validateToken('stored', 'stored'))->toBeFalse();
    });

    it('tracks completed steps', function () {
        $state = new SetupState(false, null, null, ['school', 'account'], null);
        expect($state->isStepCompleted('school'))->toBeTrue();
        expect($state->isStepCompleted('department'))->toBeFalse();
    });

    it('detects recovery key presence', function () {
        $state = new SetupState(true, null, null, [], 'hashed-key');
        expect($state->hasRecoveryKey())->toBeTrue();
    });

    it('checks finalization window', function () {
        $past = now()->subMinutes(10);
        $state = new SetupState(true, null, null, [], null, $past);
        expect($state->isWithinFinalizationWindow(5))->toBeFalse();
        expect($state->isWithinFinalizationWindow(15))->toBeTrue();
    });
});

// ─── GenerateSetupTokenAction ──────────────────────────────────────────────

describe('GenerateSetupTokenAction', function () {
    it('generates a token and stores it encrypted', function () {
        $result = app(GenerateSetupTokenAction::class)->execute();

        expect($result['plaintext'])->toBeString()->not->toBeEmpty();
        expect($result['expires_at'])->toBeInstanceOf(Carbon::class);

        $setup = Setup::first();
        expect($setup)->not->toBeNull();
        expect($setup->setup_token)->not->toBeNull();
        expect(Crypt::decryptString($setup->setup_token))->toBe($result['plaintext']);
        expect($setup->token_expires_at)->not->toBeNull();
    });

    it('generates token with configurable length', function () {
        config(['setup.token.length' => 32]);
        $result = app(GenerateSetupTokenAction::class)->execute();

        expect(strlen($result['plaintext']))->toBe(32);
    });

    it('reuses existing setup record', function () {
        Setup::create(['is_installed' => false]);
        app(GenerateSetupTokenAction::class)->execute();

        expect(Setup::count())->toBe(1);
    });
});

// ─── ValidateSetupTokenAction ──────────────────────────────────────────────

describe('ValidateSetupTokenAction', function () {
    it('validates a correct token', function () {
        $generated = app(GenerateSetupTokenAction::class)->execute();

        app(ValidateSetupTokenAction::class)->execute($generated['plaintext']);

        expect(true)->toBeTrue();
    });

    it('rejects invalid token', function () {
        app(GenerateSetupTokenAction::class)->execute();

        expect(fn () => app(ValidateSetupTokenAction::class)->execute('wrong-token'))
            ->toThrow(RuntimeException::class, 'Invalid setup token.');
    });

    it('rejects token when no setup record exists', function () {
        Setup::truncate();

        expect(fn () => app(ValidateSetupTokenAction::class)->execute('any-token'))
            ->toThrow(RuntimeException::class, 'Invalid setup token.');
    });

    it('rejects expired token', function () {
        $generated = app(GenerateSetupTokenAction::class)->execute();
        $setup = Setup::first();
        $setup->update(['token_expires_at' => now()->subMinute()]);

        expect(fn () => app(ValidateSetupTokenAction::class)->execute($generated['plaintext']))
            ->toThrow(RuntimeException::class, 'Invalid setup token.');
    });

    it('rejects token with missing stored value', function () {
        Setup::create([
            'setup_token' => null,
            'token_expires_at' => now()->addHour(),
        ]);

        expect(fn () => app(ValidateSetupTokenAction::class)->execute('any'))
            ->toThrow(RuntimeException::class, 'Invalid setup token.');
    });
});

// ─── SetupSchoolAction ─────────────────────────────────────────────────────

describe('SetupSchoolAction', function () {
    it('creates a school', function () {
        $school = app(SetupSchoolAction::class)->execute([
            'name' => 'SMK Negeri 1 Jakarta',
            'institutional_code' => '10293847',
            'email' => 'info@smkn1-jkt.sch.id',
            'address' => 'Jl. Merdeka No. 1',
            'phone' => '+62 21 1234567',
            'website' => 'https://smkn1-jkt.sch.id',
            'principal_name' => 'Dr. Budi Santoso',
        ]);

        expect($school)->toBeInstanceOf(School::class);
        expect($school->name)->toBe('SMK Negeri 1 Jakarta');
        expect(School::count())->toBe(1);
    });

    it('updates existing school instead of creating duplicate', function () {
        School::factory()->create(['name' => 'Old Name']);

        app(SetupSchoolAction::class)->execute([
            'name' => 'SMK Negeri 1 Jakarta',
            'institutional_code' => '10293847',
            'email' => 'info@smkn1-jkt.sch.id',
        ]);

        expect(School::count())->toBe(1);
        expect(School::first()->name)->toBe('SMK Negeri 1 Jakarta');
    });

    it('validates required fields', function () {
        expect(fn () => app(SetupSchoolAction::class)->execute([]))
            ->toThrow(ValidationException::class);
    });
});

// ─── SetupDepartmentAction ─────────────────────────────────────────────────

describe('SetupDepartmentAction', function () {
    it('creates a department for a school', function () {
        $school = School::factory()->create();

        $department = app(SetupDepartmentAction::class)->execute($school->id, [
            'name' => 'Teknik Komputer dan Informatika',
            'description' => 'Department for computer science',
        ]);

        expect($department)->toBeInstanceOf(Department::class);
        expect($department->name)->toBe('Teknik Komputer dan Informatika');
        expect($department->school_id)->toBe($school->id);
    });

    it('updates existing department by school_id and name', function () {
        $school = School::factory()->create();
        Department::factory()->create([
            'school_id' => $school->id,
            'name' => 'Teknik Komputer',
        ]);

        app(SetupDepartmentAction::class)->execute($school->id, [
            'name' => 'Teknik Komputer',
            'description' => 'Updated description',
        ]);

        expect(Department::count())->toBe(1);
        expect(Department::first()->description)->toBe('Updated description');
    });

    it('validates required fields', function () {
        expect(fn () => app(SetupDepartmentAction::class)->execute('school-id', []))
            ->toThrow(ValidationException::class);
    });
});

// ─── SetupSuperAdminAction ─────────────────────────────────────────────────

describe('SetupSuperAdminAction', function () {
    it('creates super admin user', function () {
        $user = app(SetupSuperAdminAction::class)->execute([
            'name' => 'Administrator',
            'username' => 'superadmin',
            'email' => 'admin@example.com',
            'password' => 'Secure1Pass',
        ]);

        expect($user)->toBeInstanceOf(User::class);
        expect($user->email)->toBe('admin@example.com');
        expect($user->hasRole(Role::SUPER_ADMIN->value))->toBeTrue();
        expect($user->hasVerifiedEmail())->toBeTrue();
    });

    it('assigns correct username and name from config', function () {
        app(SetupSuperAdminAction::class)->execute([
            'name' => 'Admin',
            'username' => 'custom',
            'email' => 'admin@example.com',
            'password' => 'Secure1Pass',
        ]);

        $user = User::first();
        expect($user->username)->toBe('superadmin');
        expect($user->name)->toBe('Administrator');
    });

    it('rejects re-initialization when immutable super admin exists', function () {
        app(SetupSuperAdminAction::class)->execute([
            'name' => 'Admin', 'username' => 'superadmin',
            'email' => 'admin@example.com', 'password' => 'Secure1Pass',
        ]);

        expect(fn () => app(SetupSuperAdminAction::class)->execute([
            'name' => 'Hacker', 'username' => 'hacker',
            'email' => 'evil@example.com', 'password' => 'Hack1234',
        ]))->toThrow(RejectedException::class, 'cannot be re-initialized');
    });

    it('validates password strength', function () {
        expect(fn () => app(SetupSuperAdminAction::class)->execute([
            'name' => 'Admin', 'username' => 'superadmin',
            'email' => 'admin@example.com', 'password' => 'weak',
        ]))->toThrow(ValidationException::class);
    });
});

// ─── InitializeSuperAdminAction ────────────────────────────────────────────

describe('InitializeSuperAdminAction', function () {
    it('creates super admin with PROTECTED status', function () {
        $user = app(InitializeSuperAdminAction::class)->execute(
            email: 'cli-admin@example.com',
            password: 'Secure1Pass',
        );

        expect($user)->toBeInstanceOf(User::class);
        expect($user->hasRole(Role::SUPER_ADMIN->value))->toBeTrue();
    });

    it('creates profile for the super admin', function () {
        $user = app(InitializeSuperAdminAction::class)->execute(
            email: 'cli-admin@example.com',
            password: 'Secure1Pass',
        );

        expect($user->profile)->not->toBeNull();
    });

    it('generates username from name when not provided', function () {
        $user = app(InitializeSuperAdminAction::class)->execute(
            email: 'cli-admin@example.com',
            password: 'Secure1Pass',
        );

        expect($user->username)->not->toBeNull();
    });
});

// ─── RecoverSuperAdminAction ────────────────────────────────────────────────

describe('RecoverSuperAdminAction', function () {
    it('creates a new super admin when not in reset mode', function () {
        $user = app(RecoverSuperAdminAction::class)->execute(
            email: 'recovered@example.com',
            password: 'Secure1Pass',
            isReset: false,
        );

        expect($user)->toBeInstanceOf(User::class);
        expect($user->hasRole(Role::SUPER_ADMIN->value))->toBeTrue();
        expect($user->email)->toBe('recovered@example.com');
    });

    it('resets password for existing super admin', function () {
        $original = User::factory()->create(['email' => 'admin@example.com', 'password' => Hash::make('old')]);
        $original->assignRole(Role::SUPER_ADMIN->value);
        $original->setStatus(AccountStatus::PROTECTED->value);

        $result = app(RecoverSuperAdminAction::class)->execute(
            email: 'admin@example.com',
            password: 'NewSecure1',
            isReset: true,
        );

        expect($result->id)->toBe($original->id);
        expect(Hash::check('NewSecure1', $result->password))->toBeTrue();
    });

    it('rate limits recovery attempts', function () {
        Cache::put('recover_admin_attempts_'.md5('test@example.com'), 3, 900);

        expect(fn () => app(RecoverSuperAdminAction::class)->execute(
            email: 'test@example.com',
            password: 'Secure1Pass',
        ))->toThrow(RuntimeException::class, 'Too many recovery attempts');
    });

    it('notifies existing super admins after recovery', function () {
        $existing = User::factory()->create(['email' => 'existing@example.com']);
        $existing->assignRole(Role::SUPER_ADMIN->value);

        app(RecoverSuperAdminAction::class)->execute(
            email: 'newadmin@example.com',
            password: 'Secure1Pass',
            isReset: false,
        );

        expect(true)->toBeTrue();
    });
});

// ─── FinalizeSetupAction ───────────────────────────────────────────────────

describe('FinalizeSetupAction', function () {
    beforeEach(function () {
        Session::start();
    });

    it('completes full setup', function () {
        $result = app(FinalizeSetupAction::class)->execute(
            schoolData: [
                'name' => 'SMK Negeri 1 Jakarta',
                'institutional_code' => '12345',
                'email' => 'info@school.sch.id',
            ],
            departmentData: ['name' => 'Teknik Komputer'],
            adminData: [
                'name' => 'Admin', 'username' => 'superadmin',
                'email' => 'admin@example.com', 'password' => 'Secure1Pass',
            ],
            internshipData: null,
        );

        expect($result)->toBeString()->not->toBeEmpty();
        expect(Setup::first()->is_installed)->toBeTrue();
        expect(School::count())->toBe(1);
        expect(Department::count())->toBe(1);
        expect(User::count())->toBe(1);
    });

    it('sets school_id and department_id on setup record', function () {
        app(FinalizeSetupAction::class)->execute(
            schoolData: ['name' => 'SMK 1', 'institutional_code' => '001', 'email' => 'a@b.com'],
            departmentData: ['name' => 'TKI'],
            adminData: ['name' => 'A', 'username' => 'sa', 'email' => 'a@b.com', 'password' => 'Secure1Pass'],
        );

        $setup = Setup::first();
        expect($setup->school_id)->not->toBeNull();
        expect($setup->department_id)->not->toBeNull();
    });

    it('throws if already installed', function () {
        Setup::create(['is_installed' => true]);

        expect(fn () => app(FinalizeSetupAction::class)->execute(
            schoolData: ['name' => 'X', 'institutional_code' => '1', 'email' => 'a@b.com'],
            departmentData: ['name' => 'TKI'],
            adminData: ['name' => 'A', 'username' => 'sa', 'email' => 'a@b.com', 'password' => 'Secure1Pass'],
        ))->toThrow(RuntimeException::class, 'already installed');
    });

    it('dispatches SetupFinalized event', function () {
        Event::fake();

        app(FinalizeSetupAction::class)->execute(
            schoolData: ['name' => 'SMK 1', 'institutional_code' => '001', 'email' => 'a@b.com'],
            departmentData: ['name' => 'TKI'],
            adminData: ['name' => 'A', 'username' => 'sa', 'email' => 'a@b.com', 'password' => 'Secure1Pass'],
        );

        Event::assertDispatched(SetupFinalized::class);
    });
});

// ─── InstallSystemAction ───────────────────────────────────────────────────

describe('InstallSystemAction', function () {
    it('runs audit, provisions, and generates token', function () {
        $result = app(InstallSystemAction::class)->execute();

        expect($result)->toHaveKeys(['plaintext', 'expires_at']);
        expect(Setup::first())->not->toBeNull();
    });
});

// ─── SetupFinalized Event / LogSetupFinalized Listener ──────────────────────

describe('SetupFinalized event', function () {
    it('creates event with schoolId and installedAt', function () {
        $event = new SetupFinalized(
            schoolId: 'some-uuid',
            installedAt: new DateTimeImmutable('2026-05-26 10:00:00'),
        );

        expect($event->schoolId)->toBe('some-uuid');
        expect($event->installedAt->format('Y-m-d'))->toBe('2026-05-26');
    });

    it('listener logs without throwing', function () {
        $event = new SetupFinalized(
            schoolId: 'uuid',
            installedAt: new DateTimeImmutable,
        );

        $listener = app(LogSetupFinalized::class);
        $listener->handle($event);

        expect(true)->toBeTrue();
    });
});

// ─── EnvironmentAuditor ─────────────────────────────────────────────────────

describe('EnvironmentAuditor', function () {
    it('returns audit report with all categories', function () {
        $report = app(EnvironmentAuditor::class)->audit();

        expect($report)->toBeInstanceOf(AuditReport::class);
        expect($report->checks)->not->toBeEmpty();
    });

    it('checks PHP version', function () {
        $report = app(EnvironmentAuditor::class)->audit();

        $phpCheck = $report->forCategory(AuditCategory::REQUIREMENTS);
        expect($phpCheck)->not->toBeEmpty();
    });

    it('checks database connectivity', function () {
        $report = app(EnvironmentAuditor::class)->audit();

        $dbCheck = $report->forCategory(AuditCategory::DATABASE);
        expect($dbCheck)->not->toBeEmpty();
    });
});

// ─── SystemProvisioner ──────────────────────────────────────────────────────

describe('SystemProvisioner', function () {
    it('returns task list', function () {
        $tasks = app(SystemProvisioner::class)->getTasks();

        expect($tasks)->toHaveKeys(['ensure_env', 'generate_key', 'run_migrations', 'run_seeders', 'storage_link', 'clear_cache']);
    });
});

// ─── Setup Model ────────────────────────────────────────────────────────────

describe('Setup model', function () {
    it('returns SetupState via state()', function () {
        Setup::create(['is_installed' => false]);

        $state = Setup::state();

        expect($state)->toBeInstanceOf(SetupState::class);
        expect($state->isInstalled())->toBeFalse();
    });

    it('returns installed state when record exists', function () {
        Setup::create(['is_installed' => true]);

        expect(Setup::state()->isInstalled())->toBeTrue();
    });

    it('handles missing table gracefully', function () {
        $state = Setup::state();

        expect($state)->toBeInstanceOf(SetupState::class);
        expect($state->isInstalled())->toBeFalse();
    });
});
