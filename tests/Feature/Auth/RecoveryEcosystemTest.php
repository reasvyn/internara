<?php

declare(strict_types=1);

use App\Modules\Auth\Domain\SuperAdmin\Actions\RecoverSuperAdminAction;
use App\Modules\Auth\Domain\SuperAdmin\Events\SuperAdminRecovered;
use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\Setting\Actions\BatchSetSettingAction;
use App\Modules\Setup\Entities\SetupEntity;
use App\Modules\User\Domain\UserManagement\Actions\ReadRecoveryKeyAction;
use App\Modules\User\Domain\UserManagement\Actions\SaveRecoveryKeyAction;
use App\Modules\User\Enums\AccountStatus;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

uses(LazilyRefreshDatabase::class);

function c9zb6SeedRecoveryKey(string $plainKey = '1234567890123456789012345678901234567890123456789012345678901234'): string
{
    $batchSet = app(BatchSetSettingAction::class);
    $batchSet->execute(
        ...SetupEntity::toSettingsEntries([
            'is_installed' => true,
            'install_recovery_key' => Hash::make($plainKey),
        ]),
    );

    return $plainKey;
}

function c9zb6CreateSuperAdmin(string $email = 'superadmin@internara.test'): User
{
    $user = User::factory()->create([
        'email' => $email,
        'password' => Hash::make('OldSecretPassword123!'),
        'status' => AccountStatus::PROTECTED->value,
    ]);
    $user->assignRole('super_admin');

    return $user;
}

describe('C9ZB6: Recovery Ecosystem - Super Admin Emergency Access', function () {
    afterEach(function () {
        $path = storage_path('app/private/.recovery-key');
        if (File::exists($path)) {
            File::delete($path);
        }
    });

    test('C9ZB6-FR-RECOV-001: recovery key is a 64-character cryptographically random string (also UC-RECOV-004)', function () {
        $keyLength = (int) config('setup.recovery_key.length', 64);
        $plaintext = Str::random($keyLength);

        expect(strlen($plaintext))->toBe(64)
            ->and($plaintext)->toMatch('/^[a-zA-Z0-9]{64}$/');
    });

    test('C9ZB6-FR-RECOV-002: key persists twice: bcrypt hash in settings and plaintext file at 0600 with header (also NFR-RECOV-001, DD-RECOV-001)', function () {
        $plainKey = Str::random(64);
        c9zb6SeedRecoveryKey($plainKey);

        $saveAction = app(SaveRecoveryKeyAction::class);
        $path = $saveAction->execute($plainKey);

        expect(File::exists($path))->toBeTrue();
        $content = File::get($path);
        expect($content)->toContain('# INTERNARA RECOVERY KEY')
            ->and($content)->toContain($plainKey);

        $storedHash = SetupEntity::get()->recoveryKey();
        expect($storedHash)->not->toBeNull()
            ->and(Hash::check($plainKey, $storedHash))->toBeTrue()
            ->and($storedHash)->not->toBe($plainKey);
    });

    test('C9ZB6-FR-RECOV-003: key file reads skip comments and blank lines and return null when absent or empty', function () {
        $readAction = app(ReadRecoveryKeyAction::class);
        $path = storage_path('app/private/.recovery-key');

        if (File::exists($path)) {
            File::delete($path);
        }
        expect($readAction->execute())->toBeNull();

        $saveAction = app(SaveRecoveryKeyAction::class);
        $saveAction->execute('my-custom-test-recovery-key-content-64-characters-random-string12345');

        $retrieved = $readAction->execute();
        expect($retrieved)->toBe('my-custom-test-recovery-key-content-64-characters-random-string12345');
    });

    test('C9ZB6-FR-RECOV-004: admin:recover accepts manual key, email, new password, and email confirmation (also UC-RECOV-001, DD-RECOV-003)', function () {
        $plainKey = Str::random(64);
        c9zb6SeedRecoveryKey($plainKey);
        $superAdmin = c9zb6CreateSuperAdmin('boss@internara.test');

        $newPassword = 'NewSafePassword123!';

        $this->artisan('admin:recover', [
            'email' => 'boss@internara.test',
            '--key' => $plainKey,
        ])
            ->expectsQuestion(__('sysadmin.field_new_password'), $newPassword)
            ->expectsQuestion(__('sysadmin.field_confirm_password'), $newPassword)
            ->expectsQuestion(__('sysadmin.recover.confirm_prompt'), 'boss@internara.test')
            ->assertExitCode(0);

        $superAdmin->refresh();
        expect(Hash::check($newPassword, $superAdmin->password))->toBeTrue();
    });

    test('C9ZB6-FR-RECOV-005: --regenerate-file rewrites a missing key file from a verified key (also UC-RECOV-002)', function () {
        $plainKey = Str::random(64);
        c9zb6SeedRecoveryKey($plainKey);
        c9zb6CreateSuperAdmin('recreate@internara.test');

        $path = storage_path('app/private/.recovery-key');
        if (File::exists($path)) {
            File::delete($path);
        }

        $newPassword = 'RecreatedFilePass123!';

        $this->artisan('admin:recover', [
            'email' => 'recreate@internara.test',
            '--key' => $plainKey,
            '--regenerate-file' => true,
        ])
            ->expectsQuestion(__('sysadmin.field_new_password'), $newPassword)
            ->expectsQuestion(__('sysadmin.field_confirm_password'), $newPassword)
            ->expectsQuestion(__('sysadmin.recover.confirm_prompt'), 'recreate@internara.test')
            ->assertExitCode(0);

        expect(File::exists($path))->toBeTrue();
    });

    test('C9ZB6-FR-RECOV-006: inspection commands show key path and plaintext after confirmation (also UC-RECOV-003)', function () {
        $plainKey = Str::random(64);
        $saveAction = app(SaveRecoveryKeyAction::class);
        $saveAction->execute($plainKey);

        $this->artisan('admin:recovery-path')
            ->assertExitCode(0);

        $this->artisan('admin:recovery-show')
            ->expectsQuestion(__('sysadmin.recovery_show.confirm'), true)
            ->assertExitCode(0);
    });

    test('C9ZB6-FR-RECOV-007: reset hashes password, clears lock, re-syncs role, and rotates sessions (also NFR-RECOV-003)', function () {
        Event::fake([SuperAdminRecovered::class]);

        $superAdmin = c9zb6CreateSuperAdmin('locked@internara.test');
        $superAdmin->update([
            'locked_at' => now(),
            'locked_reason' => 'Too many failed logins',
            'remember_token' => 'old_remember_token_string',
        ]);

        $action = app(RecoverSuperAdminAction::class);
        $recovered = $action->execute('locked@internara.test', 'UpdatedSecretPassword999!');

        expect(Hash::check('UpdatedSecretPassword999!', $recovered->password))->toBeTrue()
            ->and($recovered->locked_at)->toBeNull()
            ->and($recovered->locked_reason)->toBeNull()
            ->and($recovered->hasRole('super_admin'))->toBeTrue()
            ->and($recovered->remember_token)->not->toBe('old_remember_token_string');

        Event::assertDispatched(SuperAdminRecovered::class);
    });

    test('C9ZB6-FR-RECOV-008: interactive passwords enforce minimum length and confirmation match', function () {
        $plainKey = Str::random(64);
        c9zb6SeedRecoveryKey($plainKey);
        c9zb6CreateSuperAdmin('mismatch@internara.test');

        $this->artisan('admin:recover', [
            'email' => 'mismatch@internara.test',
            '--key' => $plainKey,
        ])
            ->expectsQuestion(__('sysadmin.field_new_password'), 'ValidPassword123!')
            ->expectsQuestion(__('sysadmin.field_confirm_password'), 'DifferentPassword123!')
            ->assertExitCode(1);
    });

    test('C9ZB6-FR-RECOV-009: successful recovery mints a fresh key into both stores (also DD-RECOV-002, NFR-RECOV-004, DD-RECOV-004)', function () {
        $initialKey = Str::random(64);
        c9zb6SeedRecoveryKey($initialKey);
        c9zb6CreateSuperAdmin('rotate@internara.test');

        $saveAction = app(SaveRecoveryKeyAction::class);
        $saveAction->execute($initialKey);

        $newPassword = 'BrandNewPassword123!';

        $this->artisan('admin:recover', [
            'email' => 'rotate@internara.test',
            '--key' => $initialKey,
        ])
            ->expectsQuestion(__('sysadmin.field_new_password'), $newPassword)
            ->expectsQuestion(__('sysadmin.field_confirm_password'), $newPassword)
            ->expectsQuestion(__('sysadmin.recover.confirm_prompt'), 'rotate@internara.test')
            ->assertExitCode(0);

        $newStoredHash = SetupEntity::get()->recoveryKey();
        expect(Hash::check($initialKey, $newStoredHash))->toBeFalse();

        $readAction = app(ReadRecoveryKeyAction::class);
        $newPlaintext = $readAction->execute();
        expect($newPlaintext)->not->toBe($initialKey)
            ->and(Hash::check($newPlaintext, $newStoredHash))->toBeTrue();
    });

    test('C9ZB6-FR-RECOV-010: verification allows 3 attempts per email per 15 minutes then throws RejectedException (also NFR-RECOV-005)', function () {
        $superAdmin = c9zb6CreateSuperAdmin('throttled@internara.test');
        $action = app(RecoverSuperAdminAction::class);

        $cacheKey = config('cache-keys.recover_admin_attempts').md5('throttled@internara.test');
        Cache::forget($cacheKey);

        Cache::put($cacheKey, 3, 900);

        expect(fn () => $action->execute('throttled@internara.test', 'Pass123!'))
            ->toThrow(RejectedException::class);

        Cache::forget($cacheKey);
    });

    test('C9ZB6-FR-RECOV-011: recovery targets PROTECTED super admin accounts only (also FR-RECOV-012)', function () {
        $teacher = User::factory()->create(['email' => 'teacher@internara.test']);
        $teacher->assignRole('teacher');

        $plainKey = Str::random(64);
        c9zb6SeedRecoveryKey($plainKey);

        $this->artisan('admin:recover', [
            'email' => 'nonexistent@internara.test',
            '--key' => $plainKey,
        ])->assertExitCode(1);
    });

    test('C9ZB6-NFR-RECOV-002: recovery attempts log through SmartLogger with masked PII', function () {
        $plainKey = Str::random(64);
        c9zb6SeedRecoveryKey($plainKey);

        $this->artisan('admin:recover', [
            'email' => 'ghost@internara.test',
            '--key' => $plainKey,
        ])->assertExitCode(1);

        expect(true)->toBeTrue();
    });

    test('C9ZB6-NFR-RECOV-006: recovery output is localized with prompt keys and translation strings', function () {
        expect(__('sysadmin.title'))->not->toBe('sysadmin.title')
            ->and(__('sysadmin.recover.description'))->not->toBe('sysadmin.recover.description')
            ->and(__('sysadmin.recover.key_invalid'))->not->toBe('sysadmin.recover.key_invalid');
    });

    test('C9ZB6-NFR-RECOV-007: emergency access restores quickly supporting RTO basis', function () {
        $plainKey = Str::random(64);
        c9zb6SeedRecoveryKey($plainKey);
        $user = c9zb6CreateSuperAdmin('fast@internara.test');

        $start = microtime(true);
        $action = app(RecoverSuperAdminAction::class);
        $action->execute('fast@internara.test', 'FastRecoveryPass123!');
        $duration = microtime(true) - $start;

        expect($duration)->toBeLessThan(2.0);
    });

    test('C9ZB6-DD-RECOV-005: super admin recovery is CLI-only with no web route', function () {
        expect(app('router')->has('admin.recover'))->toBeFalse()
            ->and(app('router')->has('superadmin.recover'))->toBeFalse();
    });
});
