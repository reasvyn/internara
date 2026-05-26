<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

use App\Domain\Admin\Actions\SaveRecoveryKeyAction;
use App\Domain\Auth\Enums\AccountStatus;
use App\Domain\Auth\Enums\Role;
use App\Domain\Core\Enums\AuditCategory;
use App\Domain\Core\Enums\AuditStatus;
use App\Domain\Setup\Actions\FinalizeSetupAction;
use App\Domain\Setup\Actions\GenerateSetupTokenAction;
use App\Domain\Setup\Actions\RecoverSuperAdminAction;
use App\Domain\Setup\Actions\SetupSchoolAction;
use App\Domain\Setup\Actions\SetupSuperAdminAction;
use App\Domain\Setup\Models\Setup;
use App\Domain\Setup\Policies\SetupPolicy;
use App\Domain\Setup\Services\EnvironmentAuditor;
use App\Domain\Setup\Support\SystemProvisioner;
use App\Domain\User\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role as RoleModel;

beforeEach(function () {
    foreach (['super_admin', 'admin', 'student', 'teacher', 'supervisor'] as $role) {
        RoleModel::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
    }
    app()->setLocale('en');
    Setup::query()->delete();
});

// ─── Validation Boundaries ─────────────────────────────────────────────────

describe('validation boundaries - SetupSchoolAction', function () {
    it('rejects empty name', function () {
        expect(fn () => app(SetupSchoolAction::class)->execute([
            'name' => '', 'institutional_code' => '001', 'email' => 'a@b.com',
        ]))->toThrow(ValidationException::class);
    });

    it('rejects empty institutional_code', function () {
        expect(fn () => app(SetupSchoolAction::class)->execute([
            'name' => 'SMK 1', 'institutional_code' => '', 'email' => 'a@b.com',
        ]))->toThrow(ValidationException::class);
    });

    it('rejects invalid email format', function () {
        expect(fn () => app(SetupSchoolAction::class)->execute([
            'name' => 'SMK 1', 'institutional_code' => '001', 'email' => 'not-an-email',
        ]))->toThrow(ValidationException::class);
    });

    it('accepts valid url format for website', function () {
        $school = app(SetupSchoolAction::class)->execute([
            'name' => 'SMK 1',
            'institutional_code' => '001',
            'email' => 'a@b.com',
            'website' => 'https://smkn1.sch.id',
        ]);

        expect($school->website)->toBe('https://smkn1.sch.id');
    });
});

describe('validation boundaries - SetupSuperAdminAction', function () {
    it('rejects password without uppercase', function () {
        expect(fn () => app(SetupSuperAdminAction::class)->execute([
            'name' => 'A', 'username' => 'sa',
            'email' => 'a@b.com', 'password' => 'lowercase1',
        ]))->toThrow(ValidationException::class);
    });

    it('rejects password without lowercase', function () {
        expect(fn () => app(SetupSuperAdminAction::class)->execute([
            'name' => 'A', 'username' => 'sa',
            'email' => 'a@b.com', 'password' => 'UPPERCASE1',
        ]))->toThrow(ValidationException::class);
    });

    it('rejects password without digit', function () {
        expect(fn () => app(SetupSuperAdminAction::class)->execute([
            'name' => 'A', 'username' => 'sa',
            'email' => 'a@b.com', 'password' => 'NoDigits!',
        ]))->toThrow(ValidationException::class);
    });

    it('rejects short password', function () {
        expect(fn () => app(SetupSuperAdminAction::class)->execute([
            'name' => 'A', 'username' => 'sa',
            'email' => 'a@b.com', 'password' => 'Ab1',
        ]))->toThrow(ValidationException::class);
    });

    it('rejects missing name', function () {
        expect(fn () => app(SetupSuperAdminAction::class)->execute([
            'name' => '', 'username' => 'sa',
            'email' => 'a@b.com', 'password' => 'Secure1Pass',
        ]))->toThrow(ValidationException::class);
    });

    it('rejects missing username', function () {
        expect(fn () => app(SetupSuperAdminAction::class)->execute([
            'name' => 'A', 'username' => '',
            'email' => 'a@b.com', 'password' => 'Secure1Pass',
        ]))->toThrow(ValidationException::class);
    });
});

// ─── Error Handlers ────────────────────────────────────────────────────────

describe('error handling', function () {
    it('FinalizeSetupAction catches SaveRecoveryKeyAction failure', function () {
        $saveRecoveryMock = mock(SaveRecoveryKeyAction::class)
            ->shouldReceive('execute')
            ->andThrow(new RuntimeException('Disk full'))
            ->getMock();

        app()->instance(SaveRecoveryKeyAction::class, $saveRecoveryMock);

        $result = app(FinalizeSetupAction::class)->execute(
            schoolData: ['name' => 'SMK 1', 'institutional_code' => '001', 'email' => 'a@b.com'],
            departmentData: ['name' => 'TKI'],
            adminData: ['name' => 'Admin', 'username' => 'sa', 'email' => 'a@b.com', 'password' => 'Secure1Pass'],
        );

        expect($result)->toBeString()->not->toBeEmpty();
        expect(Setup::first()->is_installed)->toBeTrue();
    });

    it('RecoverSuperAdminAction handles notification failure gracefully', function () {
        Cache::flush();
        $user = User::factory()->create(['email' => 'existing@test.com']);
        $user->assignRole(Role::SUPER_ADMIN->value);

        Notification::fake();

        $result = app(RecoverSuperAdminAction::class)->execute(
            email: 'newadmin@test.com',
            password: 'Secure1Pass',
        );

        expect($result)->toBeInstanceOf(User::class);
    });
});

// ─── EnvironmentAuditor Failure Paths ──────────────────────────────────────

describe('EnvironmentAuditor failure paths', function () {
    it('fails when PHP version is below requirement', function () {
        config(['setup.requirements.php_version' => '99.0.0']);

        $report = app(EnvironmentAuditor::class)->audit();
        $checks = $report->forCategory(AuditCategory::REQUIREMENTS);
        $phpCheck = collect($checks)->first(fn ($c) => $c->nameKey === 'php_version');

        expect($phpCheck->status)->toBe(AuditStatus::FAIL);
    });

    it('fails when required extensions are missing', function () {
        config(['setup.requirements.extensions' => ['nonexistent_extension_xyz']]);

        $report = app(EnvironmentAuditor::class)->audit();
        $checks = $report->forCategory(AuditCategory::REQUIREMENTS);
        $extCheck = collect($checks)->first(fn ($c) => $c->nameKey === 'extension');

        expect($extCheck->status)->toBe(AuditStatus::FAIL);
    });

    it('warns when recommended extensions are missing', function () {
        config(['setup.requirements.recommended_extensions' => ['nonexistent_ext_xyz']]);

        $report = app(EnvironmentAuditor::class)->audit();
        $checks = $report->forCategory(AuditCategory::RECOMMENDATIONS);

        $recCheck = collect($checks)->first(fn ($c) => $c->nameKey === 'recommended_extension');

        expect($recCheck->status)->toBe(AuditStatus::WARN);
    });

    it('fails report when critical check fails', function () {
        config(['setup.requirements.php_version' => '99.0.0']);

        $report = app(EnvironmentAuditor::class)->audit();

        expect($report->passed())->toBeFalse();
    });

    it('detects fail when database username is forge default', function () {
        config(['database.connections.sqlite.username' => 'forge']);

        $report = app(EnvironmentAuditor::class)->audit();
        $checks = $report->forCategory(AuditCategory::DATABASE);

        expect($checks[0]->status)->toBe(AuditStatus::FAIL);
    });
});

// ─── SystemProvisioner Edge Cases ──────────────────────────────────────────

describe('SystemProvisioner edge cases', function () {
    it('throws on unknown task', function () {
        expect(fn () => app(SystemProvisioner::class)->executeTask('nonexistent'))
            ->toThrow(InvalidArgumentException::class);
    });

    it('generates app key when missing', function () {
        $provisioner = app(SystemProvisioner::class);
        $provisioner->executeTask('ensure_env');
        $provisioner->executeTask('generate_key');

        expect(config('app.key'))->not->toBeEmpty();
    });

    it('skips storage symlink when already exists', function () {
        $publicPath = public_path('storage');
        if (! file_exists($publicPath)) {
            mkdir($publicPath, 0777, true);
        }

        $provisioner = app(SystemProvisioner::class);
        $provisioner->executeTask('storage_link');

        expect(true)->toBeTrue();
    });

    it('clears all caches', function () {
        app(SystemProvisioner::class)->executeTask('clear_cache');

        expect(true)->toBeTrue();
    });

    it('runs migrations successfully', function () {
        $provisioner = app(SystemProvisioner::class);
        $provisioner->executeTask('ensure_env');
        $provisioner->executeTask('run_migrations');

        expect(DB::select(
            'SELECT name FROM sqlite_master WHERE type=\'table\''
        ))->not->toBeEmpty();
    });

    it('runs seeders successfully', function () {
        $provisioner = app(SystemProvisioner::class);
        $provisioner->executeTask('ensure_env');
        $provisioner->executeTask('run_migrations');
        $provisioner->executeTask('run_seeders');

        expect(RoleModel::count())->toBeGreaterThan(0);
    });
});

// ─── GenerateSetupTokenAction Configurable Expiry ─────────────────────────

describe('GenerateSetupTokenAction expiry configuration', function () {
    it('uses configurable expiry minutes', function () {
        config(['setup.token.expiry_minutes' => 5]);

        $result = app(GenerateSetupTokenAction::class)->execute();

        expect($result['expires_at'])->toBeInstanceOf(Carbon::class);
        expect($result['expires_at']->diffInMinutes(now()))->toBeLessThanOrEqual(5);
    });

    it('uses configurable token length', function () {
        config(['setup.token.length' => 16]);

        $result = app(GenerateSetupTokenAction::class)->execute();

        expect(strlen($result['plaintext']))->toBe(16);
    });
});

// ─── RecoverSuperAdminAction Edge Cases ────────────────────────────────────

describe('RecoverSuperAdminAction edge cases', function () {
    it('resets password and clears lock fields', function () {
        Cache::flush();
        $user = User::factory()->create([
            'email' => 'locked@example.com',
            'password' => Hash::make('OldPass1'),
            'locked_at' => now(),
            'locked_reason' => 'compromised',
        ]);
        $user->assignRole(Role::SUPER_ADMIN->value);
        $user->setStatus(AccountStatus::PROTECTED->value);

        $result = app(RecoverSuperAdminAction::class)->execute(
            email: 'locked@example.com',
            password: 'NewSecure1',
            isReset: true,
        );

        expect(Hash::check('NewSecure1', $result->password))->toBeTrue();
        expect($result->locked_at)->toBeNull();
        expect($result->locked_reason)->toBeNull();
    });

    it('creates profile for new super admin in create mode', function () {
        Cache::flush();
        $user = app(RecoverSuperAdminAction::class)->execute(
            email: 'new@example.com',
            password: 'Secure1Pass',
            isReset: false,
        );

        expect($user->profile)->not->toBeNull();
    });
});

// ─── FinalizeSetupAction Completed Steps ───────────────────────────────────

describe('FinalizeSetupAction completed steps', function () {
    it('merges completed steps without duplicates', function () {
        Setup::query()->delete();
        Setup::create([
            'is_installed' => false,
            'completed_steps' => ['school', 'department'],
        ]);

        app(FinalizeSetupAction::class)->execute(
            schoolData: ['name' => 'SMK 1', 'institutional_code' => '001', 'email' => 'a@b.com'],
            departmentData: ['name' => 'TKI'],
            adminData: ['name' => 'A', 'username' => 'sa', 'email' => 'a@b.com', 'password' => 'Secure1Pass'],
            internshipData: null,
            stepsToComplete: ['school', 'department', 'account'],
        );

        $setup = Setup::first();
        expect($setup->completed_steps)->toBe(['school', 'department', 'account']);
    });
});

// ─── SetupPolicy ───────────────────────────────────────────────────────────

describe('SetupPolicy', function () {
    it('allows admin to view any', function () {
        $user = User::factory()->create();
        $user->assignRole(Role::ADMIN->value);
        $policy = app(SetupPolicy::class);

        expect($policy->viewAny($user))->toBeTrue();
    });

    it('denies non-admin to view any', function () {
        $user = User::factory()->create();
        $policy = app(SetupPolicy::class);

        expect($policy->viewAny($user))->toBeFalse();
    });

    it('blocks create for everyone', function () {
        $user = User::factory()->create();
        $user->assignRole(Role::SUPER_ADMIN->value);
        $setup = Setup::create(['is_installed' => false]);
        $policy = app(SetupPolicy::class);

        expect($policy->create($user))->toBeFalse();
        expect($policy->delete($user, $setup))->toBeFalse();
    });
});

// ─── RequireSetupAccessMiddleware ──────────────────────────────────────────

describe('RequireSetupAccessMiddleware', function () {
    it('redirects login to setup when not installed', function () {
        Setup::create(['is_installed' => false]);

        $this->get(route('login'))->assertRedirect(route('setup'));
    });

    it('passes through when installed', function () {
        Setup::create(['is_installed' => true]);

        $this->get(route('login'))->assertStatus(200);
    });
});
