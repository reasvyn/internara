<?php

declare(strict_types=1);

use App\Domain\Auth\Actions\ConfirmPasswordAction;
use App\Domain\Auth\Actions\GenerateRecoverySlipAction;
use App\Domain\Auth\Actions\LockUserAccountAction;
use App\Domain\Auth\Actions\LoginAction;
use App\Domain\Auth\Actions\RedeemRecoverySlipAction;
use App\Domain\Auth\Actions\ResetUserPasswordAction;
use App\Domain\Auth\Actions\UnlockUserAccountAction;
use App\Domain\Auth\Actions\UpdateUserPasswordAction;
use App\Domain\Auth\Enums\AccountStatus;
use App\Domain\Auth\Enums\Role;
use App\Domain\Auth\Models\AccountRecoveryCode;
use App\Domain\Auth\Notifications\SuperAdminRecoveredNotification;
use App\Domain\Core\Exceptions\RejectedException;
use App\Domain\Core\Support\PasswordRules;
use App\Domain\Setup\Actions\RecoverSuperAdminAction;
use App\Domain\Setup\Models\Setup;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role as RoleModel;

uses(RefreshDatabase::class);

beforeEach(function () {
    Setup::truncate();
    Setup::create(['is_installed' => true]);
    collect(Role::cases())->each(fn ($r) => RoleModel::create(['name' => $r->value, 'guard_name' => 'web']));
    Cache::flush();
    app()->setLocale('en');
});

// ─── Brute Force Protection ─────────────────────────────────────────────

describe('brute force protection', function () {
    it('locks account after exceeding failed attempt threshold', function () {
        $user = User::factory()->create(['email' => 'lock@test.com', 'password' => Hash::make('Secure1Pass')]);
        $user->setStatus(AccountStatus::VERIFIED->value);

        $threshold = 10;
        for ($i = 0; $i < $threshold; $i++) {
            try {
                app(LoginAction::class)->execute('lock@test.com', 'wrong');
            } catch (RuntimeException) {
            }
        }

        expect($user->fresh()->locked_at)->not->toBeNull();
        expect($user->fresh()->locked_reason)->toBe('too_many_failed_attempts');
    });

    it('clears failed attempts cache after successful login', function () {
        $user = User::factory()->create(['email' => 'clear@test.com', 'password' => Hash::make('Secure1Pass')]);
        $user->setStatus(AccountStatus::VERIFIED->value);

        $cacheKey = 'login-failures:'.$user->id;

        for ($i = 0; $i < 3; $i++) {
            try {
                app(LoginAction::class)->execute('clear@test.com', 'wrong');
            } catch (RuntimeException) {
            }
        }
        expect(Cache::has($cacheKey))->toBeTrue();

        app(LoginAction::class)->execute('clear@test.com', 'Secure1Pass');

        expect(Cache::has($cacheKey))->toBeFalse();
    });

    it('blocks locked account from logging in', function () {
        User::factory()->create([
            'email' => 'locked@test.com',
            'password' => Hash::make('Secure1Pass'),
            'locked_at' => now(),
            'locked_reason' => 'too_many_failed_attempts',
        ]);

        expect(fn () => app(LoginAction::class)->execute('locked@test.com', 'Secure1Pass')
        )->toThrow(RuntimeException::class, __('auth.blocked'));
    });
});

// ─── Session Security ────────────────────────────────────────────────────

describe('session security', function () {
    it('regenerates session ID after successful login via Action', function () {
        $user = User::factory()->create(['email' => 'reg@test.com', 'password' => Hash::make('Secure1Pass')]);
        $user->setStatus(AccountStatus::VERIFIED->value);

        $oldId = session()->getId();

        app(LoginAction::class)->execute('reg@test.com', 'Secure1Pass');

        expect(session()->getId())->not->toBe($oldId);
    });

    it('regenerates session after password confirmation', function () {
        $user = User::factory()->create(['password' => Hash::make('correct')]);
        $oldId = session()->getId();

        app(ConfirmPasswordAction::class)->execute($user, 'correct');

        expect(session('auth.password_confirmed_at'))->not->toBeNull();
    });

    it('generates new session ID after login (regenerate)', function () {
        $user = User::factory()->create(['email' => 'leak@test.com', 'password' => Hash::make('Secure1Pass')]);
        $user->setStatus(AccountStatus::VERIFIED->value);
        $oldId = session()->getId();

        app(LoginAction::class)->execute('leak@test.com', 'Secure1Pass');

        expect(session()->getId())->not->toBe($oldId);
    });
});

// ─── Rate Limiting ───────────────────────────────────────────────────────

describe('rate limiting', function () {
    it('auto-locks account after failed login threshold', function () {
        $user = User::factory()->create(['email' => 'ratelimit@test.com', 'password' => Hash::make('Secure1Pass')]);
        $user->setStatus(AccountStatus::VERIFIED->value);

        for ($i = 0; $i < 10; $i++) {
            try {
                app(LoginAction::class)->execute('ratelimit@test.com', 'wrong');
            } catch (RuntimeException) {
            }
        }

        expect($user->fresh()->locked_at)->not->toBeNull();
    });
});

// ─── Password Security ───────────────────────────────────────────────────

describe('password security', function () {
    it('rejects weak password via UpdateUserPasswordAction', function () {
        $user = User::factory()->create();
        expect(fn () => app(UpdateUserPasswordAction::class)->execute($user, 'weak')
        )->toThrow(ValidationException::class);
    });

    it('rejects password without uppercase', function () {
        $validator = Validator::make(['password' => 'lowercase1'], [
            'password' => PasswordRules::default(),
        ]);
        expect($validator->fails())->toBeTrue();
    });

    it('rejects password without number', function () {
        $validator = Validator::make(['password' => 'NoNumber'], [
            'password' => PasswordRules::default(),
        ]);
        expect($validator->fails())->toBeTrue();
    });

    it('validates password with mixed case and number', function () {
        $validator = Validator::make(['password' => 'Valid1Pass'], [
            'password' => PasswordRules::default(),
        ]);
        expect($validator->passes())->toBeTrue();
    });
});

// ─── Recovery Code Security ──────────────────────────────────────────────

describe('recovery code security', function () {
    it('hashes recovery codes before storage', function () {
        $user = User::factory()->create();
        $result = app(GenerateRecoverySlipAction::class)->execute($user);

        $stored = AccountRecoveryCode::first();
        expect($stored->code_hash)->not->toBe($result['plaintext'][0]);
        expect(Hash::isHashed($stored->code_hash))->toBeTrue();
    });

    it('marks recovery code as used after redemption', function () {
        $user = User::factory()->create(['username' => 'redeemuser']);
        $hash = Hash::make(strtoupper('USEDCODE123'));
        AccountRecoveryCode::create([
            'user_id' => $user->id, 'code_hash' => $hash,
            'generated_at' => now(),
        ]);

        app(RedeemRecoverySlipAction::class)->execute('redeemuser', 'USEDCODE123', 'NewPass1');

        $code = AccountRecoveryCode::first();
        expect($code->used_at)->not->toBeNull();
    });

    it('prevents reusing the same recovery code', function () {
        $user = User::factory()->create(['username' => 'reuseuser']);
        $hash = Hash::make(strtoupper('REUSE123'));
        AccountRecoveryCode::create([
            'user_id' => $user->id, 'code_hash' => $hash,
            'generated_at' => now(),
        ]);

        app(RedeemRecoverySlipAction::class)->execute('reuseuser', 'REUSE123', 'NewPass1');

        expect(fn () => app(RedeemRecoverySlipAction::class)->execute('reuseuser', 'REUSE123', 'NewPass2')
        )->toThrow(RuntimeException::class);
    });

    it('converts recovery code to uppercase before comparison', function () {
        $user = User::factory()->create(['username' => 'caseuser']);
        $hash = Hash::make(strtoupper('CASE123'));
        AccountRecoveryCode::create([
            'user_id' => $user->id, 'code_hash' => $hash,
            'generated_at' => now(),
        ]);

        app(RedeemRecoverySlipAction::class)->execute('caseuser', 'case123', 'NewPass1');

        expect(Hash::check('CASE123', AccountRecoveryCode::first()->code_hash))->toBeTrue();
    });
});

// ─── Super Admin Integrity ───────────────────────────────────────────────

describe('super admin integrity', function () {
    it('blocks password reset for super admin through admin interface', function () {
        $sa = User::factory()->create()->assignRole(Role::SUPER_ADMIN->value);

        expect(fn () => app(ResetUserPasswordAction::class)->execute($sa)
        )->toThrow(RejectedException::class);
    });

    it('blocks locking super admin', function () {
        $sa = User::factory()->create()->assignRole(Role::SUPER_ADMIN->value);

        expect(fn () => app(LockUserAccountAction::class)->execute($sa)
        )->toThrow(RuntimeException::class);
    });

    it('blocks unlocking super admin', function () {
        $sa = User::factory()->create()->assignRole(Role::SUPER_ADMIN->value);

        expect(fn () => app(UnlockUserAccountAction::class)->execute($sa)
        )->toThrow(RuntimeException::class);
    });
});

// ─── Account Status Guards ───────────────────────────────────────────────

describe('account status guards', function () {
    it('blocks suspended accounts from login', function () {
        $user = User::factory()->create(['email' => 'suspended@test.com', 'password' => Hash::make('Secure1Pass')]);
        $user->setStatus(AccountStatus::SUSPENDED->value);

        expect(fn () => app(LoginAction::class)->execute('suspended@test.com', 'Secure1Pass')
        )->toThrow(RuntimeException::class);
    });

    it('blocks archived accounts from login', function () {
        $user = User::factory()->create(['email' => 'archived@test.com', 'password' => Hash::make('Secure1Pass')]);
        $user->setStatus(AccountStatus::ARCHIVED->value);

        expect(fn () => app(LoginAction::class)->execute('archived@test.com', 'Secure1Pass')
        )->toThrow(RuntimeException::class);
    });

    it('allows inactive accounts to login (read-only access)', function () {
        $user = User::factory()->create(['email' => 'inactive@test.com', 'password' => Hash::make('Secure1Pass')]);
        $user->setStatus(AccountStatus::INACTIVE->value);

        $result = app(LoginAction::class)->execute('inactive@test.com', 'Secure1Pass');

        expect($result->email)->toBe('inactive@test.com');
    });

    it('blocks provisioned accounts from login', function () {
        $user = User::factory()->create(['email' => 'provisioned@test.com', 'password' => Hash::make('Secure1Pass')]);
        $user->setStatus(AccountStatus::PROVISIONED->value);

        expect(fn () => app(LoginAction::class)->execute('provisioned@test.com', 'Secure1Pass')
        )->toThrow(RuntimeException::class);
    });

    it('allows verified accounts to login', function () {
        $user = User::factory()->create(['email' => 'verified@test.com', 'password' => Hash::make('Secure1Pass')]);
        $user->setStatus(AccountStatus::VERIFIED->value);

        $result = app(LoginAction::class)->execute('verified@test.com', 'Secure1Pass');

        expect($result->email)->toBe('verified@test.com');
    });
});

// ─── Notification Security ───────────────────────────────────────────────

describe('notification security', function () {
    it('notifies existing super admins on recovery', function () {
        Notification::fake();
        $existing = User::factory()->create()->assignRole(Role::SUPER_ADMIN->value);

        app(RecoverSuperAdminAction::class)->execute(
            email: 'newadmin@test.com',
            password: 'NewPass123',
        );

        Notification::assertSentTo($existing, SuperAdminRecoveredNotification::class);
    });
});
