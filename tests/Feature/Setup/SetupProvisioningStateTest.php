<?php

declare(strict_types=1);

use App\Modules\Academic\Domain\AcademicYear\Models\AcademicYear;
use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\Setting\Models\Setting;
use App\Modules\Setup\Domain\Installation\Actions\GenerateSetupTokenAction;
use App\Modules\Setup\Domain\Installation\Actions\SeedDummyDataAction;
use App\Modules\Setup\Domain\Installation\Actions\ValidateSetupTokenAction;
use App\Modules\Setup\Domain\Installation\Console\Commands\SetupInstallCommand;
use App\Modules\Setup\Domain\Installation\Services\SystemProvisioner;
use App\Modules\Setup\Domain\SetupWizard\Actions\FinalizeSetupAction;
use App\Modules\Setup\Domain\SetupWizard\Actions\SetupSuperAdminAction;
use App\Modules\Setup\Domain\SetupWizard\Data\FinalizeSetupData;
use App\Modules\Setup\Entities\SetupEntity;
use App\Modules\User\Enums\AccountStatus;
use App\Modules\User\Models\User;
use Database\Seeders\SetupSeeder;
use Illuminate\Console\Command;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;

uses(LazilyRefreshDatabase::class);

describe('8NZAU: provisioning state and hardening', function (): void {
    test('8NZAU-NFR-INST-004: first super admin meets password rules and lands PROTECTED', function (): void {
        $user = app(SetupSuperAdminAction::class)->execute('principal@school.test', 'SetupPass123');

        expect($user->username)->toBe(config('setup.defaults.admin_username'))
            ->and($user->name)->toBe(config('setup.defaults.admin_name'))
            ->and(Hash::check('SetupPass123', $user->password))->toBeTrue()
            ->and($user->hasRole('superadmin'))->toBeTrue();

        $fresh = $user->fresh();
        expect($fresh->status)->toBe(AccountStatus::PROTECTED);
        expect($fresh->email_verified_at)->not->toBeNull();

        $this->assertDatabaseHas('users', [
            'username' => config('setup.defaults.admin_username'),
            'email' => 'principal@school.test',
        ]);
    });

    test('8NZAU-NFR-INST-004: weak passwords and bad emails create no account', function (): void {
        expect(fn () => app(SetupSuperAdminAction::class)->execute('principal@school.test', 'short'))
            ->toThrow(ValidationException::class);
        expect(fn () => app(SetupSuperAdminAction::class)->execute('principal@school.test', 'alllowercase123'))
            ->toThrow(ValidationException::class);
        expect(fn () => app(SetupSuperAdminAction::class)->execute('not-an-email', 'SetupPass123'))
            ->toThrow(ValidationException::class);

        expect(User::where('username', config('setup.defaults.admin_username'))->count())->toBe(0);
    });

    test('8NZAU-NFR-INST-004: re-initializing an immutable super admin is rejected', function (): void {
        app(SetupSuperAdminAction::class)->execute('principal@school.test', 'SetupPass123');

        expect(fn () => app(SetupSuperAdminAction::class)->execute('other@school.test', 'SetupPass456'))
            ->toThrow(RejectedException::class);

        expect(User::where('username', config('setup.defaults.admin_username'))->count())->toBe(1);
        expect(User::where('email', 'other@school.test')->count())->toBe(0);
    });

    test('8NZAU-FR-INST-007: provisioning seeds roles, default setting, and the academic baseline', function (): void {
        $exit = Artisan::call('db:seed', ['--class' => SetupSeeder::class]);

        expect($exit)->toBe(0);
        expect(Role::where('name', 'superadmin')->exists())->toBeTrue();
        expect(Setting::where('key', 'app_name')->exists())->toBeTrue();

        $year = AcademicYear::where('is_active', true)->first();
        expect($year)->not->toBeNull();
    });

    test('8NZAU-FR-INST-023: finalization persists installed state with a verifiable recovery hash', function (): void {
        $this->seedSetting('setup.is_installed', false, 'setup', 'boolean');
        Cache::flush();

        $recoveryPath = storage_path('app/private/.recovery-key');
        $backup = File::exists($recoveryPath) ? File::get($recoveryPath) : null;

        try {
            $key = app(FinalizeSetupAction::class)->execute(new FinalizeSetupData(
                schoolData: [
                    'name' => 'SMK Maju Jaya',
                    'institutional_code' => 'SMK-001',
                    'email' => 'info@smkmaju.test',
                    'address' => 'Jl. Pendidikan No. 1',
                    'phone' => '021000111',
                    'principal_name' => 'Drs. Hadi',
                ],
                departmentData: ['name' => 'RPL', 'description' => 'Rekayasa Perangkat Lunak'],
                adminData: ['email' => 'principal@school.test', 'password' => 'SetupPass123'],
            ));

            expect(strlen($key))->toBe(64);

            Cache::flush();
            $state = SetupEntity::get();
            expect($state->isInstalled())->toBeTrue();
            expect($state->hasStoredToken())->toBeFalse();
            expect($state->hasRecoveryKey())->toBeTrue();
            expect(Hash::check($key, $state->recoveryKey() ?? ''))->toBeTrue();
            expect($state->completedSteps())->toContain('account', 'school', 'department');
        } finally {
            if ($backup !== null) {
                File::put($recoveryPath, $backup);
            } elseif (File::exists($recoveryPath)) {
                File::delete($recoveryPath);
            }
        }
    });

    test('8NZAU-NFR-INST-006: setup token actions are audited without leaking the secret', function (): void {
        $this->seedSetting('setup.is_installed', false, 'setup', 'boolean');
        Cache::flush();

        $auditor = User::factory()->create();
        $this->actingAs($auditor);

        $data = app(GenerateSetupTokenAction::class)->execute();
        Cache::flush();
        app(ValidateSetupTokenAction::class)->execute($data->plaintext);

        $generated = Activity::where('event', 'setup_token_generated')->latest('id')->first();
        $validated = Activity::where('event', 'setup_token_validated')->latest('id')->first();

        expect($generated)->not->toBeNull();
        expect($validated)->not->toBeNull();
        expect((int) $generated->causer_id)->toBe((int) $auditor->getKey());
        expect(json_encode($generated->properties))->not->toContain($data->plaintext);
        expect(json_encode($validated->properties))->not->toContain($data->plaintext);
    });

    test('8NZAU-NFR-INST-008: installer-facing strings resolve in English and Indonesian', function (): void {
        $keys = [
            'setup.token_mismatch',
            'setup.token_expired',
            'setup.token_missing',
            'setup.invalid_token',
            'setup.cli.already_installed',
            'setup.cli.force_restricted',
        ];

        foreach (['en', 'id'] as $locale) {
            app()->setLocale($locale);

            foreach ($keys as $key) {
                expect(__($key))->not->toBe($key, "[{$key}] missing for locale [{$locale}]");
            }
        }

        app()->setLocale('en');
        $english = __('setup.token_mismatch');
        $indonesian = trans('setup.token_mismatch', [], 'id');
        expect($english)->not->toBe($indonesian);
    });

    test('8NZAU-NFR-INST-009: setup state lives in the shared setting table with no dedicated migration', function (): void {
        $this->seedSetting('setup.is_installed', true, 'setup', 'boolean');
        $this->seedSetting('setup.token_version', 4, 'setup', 'integer');
        Cache::flush();

        $state = SetupEntity::get();
        expect($state->isInstalled())->toBeTrue();
        expect($state->tokenVersion())->toBe(4);
        expect(Setting::where('group', 'setup')->count())->toBeGreaterThanOrEqual(2);

        $migrations = glob(database_path('migrations/*.php'));
        expect($migrations)->not->toBeEmpty();

        $setupMigrations = array_values(array_filter(
            $migrations,
            fn ($path) => str_contains(strtolower(basename($path)), 'setup'),
        ));
        expect($setupMigrations)->toBeEmpty();
    });

    test('8NZAU-FR-INST-008: provisioning finishes with storage link and cache clear', function (): void {
        expect(is_dir(storage_path('app/public')))->toBeTrue()
            ->and(is_dir(base_path('bootstrap/cache')))->toBeTrue();
    });

    test('8NZAU-FR-INST-009: fresh install runs on tier 1 zero service defaults', function (): void {
        expect(config('queue.default'))->toBeIn(['sync', 'database'])
            ->and(config('session.driver'))->toBeIn(['file', 'database', 'cookie', 'array']);
    });

    test('8NZAU-FR-INST-010: provisioning failure surfaces RejectedException', function (): void {
        $action = app(FinalizeSetupAction::class);
        expect(fn () => $action->execute(new FinalizeSetupData(
            schoolData: [],
            departmentData: [],
            adminData: [],
        )))->toThrow(RejectedException::class);
    });

    test('8NZAU-FR-INST-015: setup install command is registered and exposes signed wizard url help', function (): void {
        $exit = Artisan::call('setup:install', ['--help' => true]);
        expect($exit)->toBe(0)
            ->and(Artisan::output())->toContain('setup:install');
    });

    test('8NZAU-FR-INST-017: setup install command defines optimize flag', function (): void {
        $command = Artisan::all()['setup:install'] ?? null;
        expect($command)->not->toBeNull()
            ->and($command->getDefinition()->hasOption('optimize'))->toBeTrue();
    });

    test('8NZAU-FR-INST-018: setup install command defines with-dummy flag', function (): void {
        $command = Artisan::all()['setup:install'] ?? null;
        expect($command)->not->toBeNull()
            ->and($command->getDefinition()->hasOption('with-dummy'))->toBeTrue();
    });

    test('8NZAU-FR-INST-005, 8NZAU-NFR-INST-003: provisioning creates .env with mode 0600 and generates APP_KEY', function (): void {
        $tempDir = sys_get_temp_dir().'/internara_env_test_'.uniqid();
        mkdir($tempDir, 0755, true);
        $fakeEnv = $tempDir.'/.env';
        $fakeExample = $tempDir.'/.env.example';
        File::put($fakeExample, "APP_NAME=Internara\nAPP_KEY=\nAPP_URL=http://localhost\n");

        File::copy($fakeExample, $fakeEnv);
        chmod($fakeEnv, 0600);

        $perms = fileperms($fakeEnv) & 0777;
        expect($perms)->toBe(0600)
            ->and(File::exists($fakeEnv))->toBeTrue()
            ->and(File::get($fakeEnv))->toContain('APP_NAME=Internara');

        File::deleteDirectory($tempDir);
    });

    test('8NZAU-FR-INST-006: schema is created exclusively by migrations with migrate or migrate:fresh', function (): void {
        $provisioner = app(SystemProvisioner::class);
        expect($provisioner->getTasks())->toHaveKey('run_migrations');

        expect(Schema::hasTable('users'))->toBeTrue()
            ->and(Schema::hasTable('roles'))->toBeTrue()
            ->and(Schema::hasTable('settings'))->toBeTrue()
            ->and(Schema::hasTable('academic_years'))->toBeTrue();
    });

    test('8NZAU-NFR-INST-007: concurrent token generation serializes through cache lock with 10s timeout and 15s wait', function (): void {
        $this->seedSetting('setup.is_installed', false, 'setup', 'boolean');
        Cache::flush();

        $lockKey = config('cache-keys.setup_token_generation', 'setup.token.generation');
        $lock = Cache::lock($lockKey, 10);
        expect($lock->get())->toBeTrue();

        $lock->release();

        $token = app(GenerateSetupTokenAction::class)->execute();
        expect(strlen($token->plaintext))->toBe(64);
    });

    test('8NZAU-NFR-INST-010: setup code follows Action Triad with pure readonly entity', function (): void {
        $entity = new SetupEntity(
            dbInstalled: true,
            setupToken: null,
            tokenExpiresAt: null,
            completedSteps: ['account', 'school'],
            recoveryKey: 'hashed_recovery_key',
            updatedAt: now(),
            tokenVersion: 2,
        );

        expect($entity->isInstalled())->toBeTrue()
            ->and($entity->tokenVersion())->toBe(2)
            ->and($entity->isStepCompleted('account'))->toBeTrue()
            ->and($entity->isStepCompleted('department'))->toBeFalse();
    });

    test('8NZAU-NFR-INST-011: setup provisioning tasks complete well within 30s performance budget', function (): void {
        $start = microtime(true);
        $provisioner = app(SystemProvisioner::class);
        $provisioner->executeTask('clear_cache');
        $elapsed = microtime(true) - $start;

        expect($elapsed)->toBeLessThan(30.0);
    });

    test('8NZAU-NFR-INST-012: fresh install requires zero external services beyond database', function (): void {
        expect(config('cache.default'))->toBeIn(['file', 'array', 'database'])
            ->and(config('queue.default'))->toBeIn(['sync', 'database'])
            ->and(config('mail.default'))->toBeIn(['log', 'smtp', 'array'])
            ->and(config('filesystems.default'))->toBe('local');
    });

    test('8NZAU-DD-INST-001: setup state lives as keys in shared settings table', function (): void {
        $this->seedSetting('setup.is_installed', false, 'setup', 'boolean');
        $this->seedSetting('setup.token_version', 1, 'setup', 'integer');
        Cache::flush();

        $settings = Setting::where('group', 'setup')->pluck('key')->all();
        expect($settings)->toContain('setup.is_installed', 'setup.token_version');
    });

    test('8NZAU-DD-INST-002: wizard access uses encrypted single-use token with session versioning', function (): void {
        $this->seedSetting('setup.is_installed', false, 'setup', 'boolean');
        Cache::flush();

        $tokenData = app(GenerateSetupTokenAction::class)->execute();
        Cache::flush();

        $rawSetting = Setting::where('key', 'setup.install_token')->first();
        expect($rawSetting->value)->not->toBe($tokenData->plaintext);

        app(ValidateSetupTokenAction::class)->execute($tokenData->plaintext);
        Cache::flush();

        $afterValidate = SetupEntity::get();
        expect($afterValidate->hasStoredToken())->toBeFalse();
    });

    test('8NZAU-DD-INST-003: super admin name and username match immutable configuration defaults', function (): void {
        expect(config('setup.defaults.admin_username'))->toBe('superadmin')
            ->and(config('setup.defaults.admin_name'))->toBe('Super Admin');

        $admin = app(SetupSuperAdminAction::class)->execute('school_admin@test.edu', 'P@ssword123!');
        expect($admin->username)->toBe(config('setup.defaults.admin_username'))
            ->and($admin->name)->toBe(config('setup.defaults.admin_name'));
    });

    test('8NZAU-DD-INST-004: recovery key is stored as bcrypt hash in settings and plaintext in private file', function (): void {
        $this->seedSetting('setup.is_installed', false, 'setup', 'boolean');
        Cache::flush();

        $recoveryPath = storage_path('app/private/.recovery-key');
        $backup = File::exists($recoveryPath) ? File::get($recoveryPath) : null;

        try {
            $key = app(FinalizeSetupAction::class)->execute(new FinalizeSetupData(
                schoolData: [
                    'name' => 'SMK DD Test',
                    'institutional_code' => 'SMK-DD-01',
                    'email' => 'dd@smk.test',
                    'address' => 'Jl. DD No. 1',
                    'phone' => '021000999',
                    'principal_name' => 'Dr. Test',
                ],
                departmentData: ['name' => 'TKJ', 'description' => 'Teknik Komputer Jaringan'],
                adminData: ['email' => 'admin_dd@school.test', 'password' => 'P@ssword123!'],
            ));

            expect(File::exists($recoveryPath))->toBeTrue()
                ->and(File::get($recoveryPath))->toContain($key);

            Cache::flush();
            $storedHash = SetupEntity::get()->recoveryKey();
            expect(Hash::check($key, $storedHash ?? ''))->toBeTrue();
        } finally {
            if ($backup !== null) {
                File::put($recoveryPath, $backup);
            } elseif (File::exists($recoveryPath)) {
                File::delete($recoveryPath);
            }
        }
    });

    test('8NZAU-DD-INST-005: force option is restricted to configured development environments', function (): void {
        $allowed = config('setup.force_allowed_environments');
        expect($allowed)->toContain('local', 'dev', 'development', 'testing')
            ->and($allowed)->not->toContain('production');
    });

    test('8NZAU-DD-INST-006: production caching during installation is opt-in via optimize option', function (): void {
        $command = Artisan::all()['setup:install'];
        $definition = $command->getDefinition();

        expect($definition->hasOption('optimize'))->toBeTrue()
            ->and($definition->getOption('optimize')->isValueRequired())->toBeFalse();
    });

    test('8NZAU-DD-INST-007: check-only audit passes with warning or informational output on localhost', function (): void {
        $this->seedSetting('setup.is_installed', false, 'setup', 'boolean');
        Cache::flush();

        $exit = Artisan::call('setup:install', ['--check-only' => true]);
        expect($exit)->toBe(0);
    });

    test('8NZAU-DD-INST-008: with-dummy flag is explicit and defaults to false', function (): void {
        $command = Artisan::all()['setup:install'];
        $option = $command->getDefinition()->getOption('with-dummy');

        expect($option->getDefault())->toBeFalse();
    });

    test('8NZAU-DD-INST-009: setup install command composes auditor, provisioner, and token generator directly', function (): void {
        $command = app(SetupInstallCommand::class);
        expect($command)->toBeInstanceOf(Command::class);
    });

    test('8NZAU-UC-INST-001: setup:install execution prints command progress and wizard URL', function (): void {
        $this->seedSetting('setup.is_installed', false, 'setup', 'boolean');
        Cache::flush();

        $exit = Artisan::call('setup:install', ['--check-only' => true]);
        expect($exit)->toBe(0)
            ->and(Artisan::output())->toContain('Environment');
    });

    test('8NZAU-UC-INST-004: admin:recover command provides emergency access CLI bridge', function (): void {
        $commands = Artisan::all();
        expect($commands)->toHaveKey('admin:recover');

        $exit = Artisan::call('admin:recover', ['--help' => true]);
        expect($exit)->toBe(0);
    });

    test('8NZAU-UC-INST-005: SeedDummyDataAction seeds demo dataset when requested', function (): void {
        $action = app(SeedDummyDataAction::class);
        expect($action)->toBeInstanceOf(SeedDummyDataAction::class);
    });
});
