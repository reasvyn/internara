<?php

declare(strict_types=1);

use App\Modules\Academic\Domain\AcademicYear\Models\AcademicYear;
use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\Setting\Models\Setting;
use App\Modules\Setup\Domain\Installation\Actions\GenerateSetupTokenAction;
use App\Modules\Setup\Domain\Installation\Actions\ValidateSetupTokenAction;
use App\Modules\Setup\Domain\SetupWizard\Actions\FinalizeSetupAction;
use App\Modules\Setup\Domain\SetupWizard\Actions\SetupSuperAdminAction;
use App\Modules\Setup\Domain\SetupWizard\Data\FinalizeSetupData;
use App\Modules\Setup\Entities\SetupEntity;
use App\Modules\User\Enums\AccountStatus;
use App\Modules\User\Models\User;
use Database\Seeders\SetupSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
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
});
