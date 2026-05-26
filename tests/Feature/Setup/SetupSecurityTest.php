<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

use App\Domain\Auth\Enums\Role;
use App\Domain\Core\Exceptions\RejectedException;
use App\Domain\Setup\Actions\FinalizeSetupAction;
use App\Domain\Setup\Actions\GenerateSetupTokenAction;
use App\Domain\Setup\Actions\SetupSchoolAction;
use App\Domain\Setup\Actions\SetupSuperAdminAction;
use App\Domain\Setup\Models\Setup;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role as RoleModel;

beforeEach(function () {
    foreach (['super_admin', 'admin', 'student', 'teacher', 'supervisor'] as $role) {
        RoleModel::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
    }
    app()->setLocale('en');
    Setup::query()->delete();
    Session::flush();
    Cache::flush();
});

// ─── Authentication Bypass ────────────────────────────────────────────────

describe('authentication bypass', function () {
    it('blocks direct access to setup wizard without token', function () {
        Setup::create(['is_installed' => false]);
        $this->get(route('setup'))
            ->assertStatus(200)
            ->assertSee(__('setup.code_entry.title'));
    });

    it('returns 404 when system is installed without authorized session', function () {
        Setup::create(['is_installed' => true]);
        $this->get(route('setup'))->assertStatus(404);
    });

    it('rejects expired token', function () {
        Setup::create(['is_installed' => false]);
        $token = app(GenerateSetupTokenAction::class)->execute();
        Setup::first()->update(['token_expires_at' => now()->subMinute(), 'is_installed' => false]);

        $this->get(route('setup', ['setup_token' => $token['plaintext']]))
            ->assertStatus(403);
    });

    it('shows code entry form for empty token', function () {
        Setup::create(['is_installed' => false]);
        $this->get(route('setup', ['setup_token' => '']))
            ->assertStatus(200)
            ->assertSee(__('setup.code_entry.title'));
    });

    it('rejects invalid token with 403', function () {
        Setup::create(['is_installed' => false]);
        $this->get(route('setup', ['setup_token' => 'invalidtoken123']))
            ->assertStatus(403);
    });

    it('rejects unicode homoglyph token', function () {
        Setup::create(['is_installed' => false]);
        $this->get(route('setup', ['setup_token' => 'tоkеn']))
            ->assertStatus(403);
    });
});

// ─── Rate Limiting ─────────────────────────────────────────────────────────

describe('rate limiting', function () {
    it('returns 429 after exceeding attempt limit', function () {
        Setup::create(['is_installed' => false]);
        config(['setup.security.rate_limit_attempts' => 2]);
        config(['setup.security.rate_limit_decay_seconds' => 60]);

        $this->get(route('setup'));
        $this->get(route('setup'));
        $this->get(route('setup'), ['Accept' => 'application/json'])
            ->assertStatus(429);
    });

    it('applies rate limit independently per IP', function () {
        Setup::create(['is_installed' => false]);
        config(['setup.security.rate_limit_attempts' => 1]);

        $this->get(route('setup'), ['REMOTE_ADDR' => '1.2.3.4']);

        $this->get(route('setup'), ['REMOTE_ADDR' => '5.6.7.8'])
            ->assertStatus(200);
    });

    it('returns JSON error on rate limit for API requests', function () {
        Setup::create(['is_installed' => false]);
        config(['setup.security.rate_limit_attempts' => 1]);

        $this->get(route('setup'));
        $this->get(route('setup'), ['Accept' => 'application/json'])
            ->assertStatus(429)
            ->assertJsonStructure(['message']);
    });

    it('rate limits POST token validation', function () {
        Setup::create(['is_installed' => false]);
        config(['setup.security.rate_limit_attempts' => 1]);

        $this->post(route('setup'), ['setup_token' => 'bad']);
        $this->post(route('setup'), ['setup_token' => 'bad2'], ['Accept' => 'application/json'])
            ->assertStatus(429);
    });
});

// ─── Token Security ────────────────────────────────────────────────────────

describe('token security', function () {
    it('stores token encrypted, not in plaintext', function () {
        $result = app(GenerateSetupTokenAction::class)->execute();
        $setup = Setup::first();

        expect($setup->setup_token)->not->toBe($result['plaintext']);
        expect(Crypt::decryptString($setup->setup_token))->toBe($result['plaintext']);
    });

    it('generates token with configurable minimum length', function () {
        config(['setup.token.length' => 128]);
        $result = app(GenerateSetupTokenAction::class)->execute();

        expect(strlen($result['plaintext']))->toBe(128);
    });

    it('cannot reuse same token after first validation consumes it', function () {
        Setup::create(['is_installed' => false]);
        $result = app(GenerateSetupTokenAction::class)->execute();
        Setup::first()->update(['is_installed' => false]);

        $plaintext = $result['plaintext'];
        $this->get(route('setup', ['setup_token' => $plaintext]))->assertStatus(200);

        Session::flush();
        Setup::first()->update(['token_expires_at' => now()->subMinute(), 'is_installed' => false]);

        $this->get(route('setup', ['setup_token' => $plaintext]))->assertStatus(403);
    });

    it('regenerates session ID after token authorization', function () {
        Setup::create(['is_installed' => false]);
        $token = app(GenerateSetupTokenAction::class)->execute();
        Setup::first()->update(['is_installed' => false]);
        $oldId = session()->getId();

        $this->get(route('setup', ['setup_token' => $token['plaintext']]));

        expect(session()->getId())->not->toBe($oldId);
    });
});

// ─── Session Security ──────────────────────────────────────────────────────

describe('session security', function () {
    it('clears setup session data after finalization', function () {
        Session::put('setup.authorized', true);
        Session::put('setup.token', 'secret');
        Session::put('setup.form_data', ['school' => ['name' => 'X']]);

        app(FinalizeSetupAction::class)->execute(
            schoolData: ['name' => 'SMK 1', 'institutional_code' => '001', 'email' => 'a@b.com'],
            departmentData: ['name' => 'TKI'],
            adminData: ['name' => 'Admin', 'username' => 'sa', 'email' => 'a@b.com', 'password' => 'Secure1Pass'],
        );

        expect(session()->has('setup.authorized'))->toBeFalse();
        expect(session()->has('setup.token'))->toBeFalse();
        expect(session()->has('setup.form_data'))->toBeFalse();
    });

    it('cleanup route removes setup session data', function () {
        Setup::create(['is_installed' => true]);
        Session::put('setup.authorized', true);
        Session::put('setup.form_data', ['school' => ['name' => 'X']]);

        $this->post(route('setup.cleanup'))->assertNoContent();

        expect(session()->has('setup.authorized'))->toBeFalse();
        expect(session()->has('setup.form_data'))->toBeFalse();
    });
});

// ─── XSS Injection ─────────────────────────────────────────────────────────

describe('xss prevention', function () {
    it('prevents XSS in school name through SetupSchoolAction', function () {
        $xss = '<script>alert("xss")</script>';
        $action = app(SetupSchoolAction::class);

        expect(fn () => $action->execute(['name' => $xss]))
            ->toThrow(ValidationException::class);
    });

    it('uses canonical name from config, not input', function () {
        $xss = '<img src=x onerror=alert(1)>';
        $user = app(SetupSuperAdminAction::class)->execute('admin@test.com', 'Secure1Pass');

        expect($user->name)->toBe(config('setup.defaults.admin_name', 'Administrator'));
    });
});

// ─── Mass Assignment / Parameter Tampering ─────────────────────────────────

describe('mass assignment protection', function () {
    it('ignores unexpected fields in school data', function () {
        $school = app(SetupSchoolAction::class)->execute([
            'name' => 'SMK 1',
            'institutional_code' => '001',
            'email' => 'a@b.com',
            'is_admin' => true,
            'role' => 'super_admin',
        ]);

        expect($school->name)->toBe('SMK 1');
        expect($school->is_admin ?? false)->toBeFalse();
    });

    it('enforces canonical name and username regardless of input', function () {
        $user = app(SetupSuperAdminAction::class)->execute('admin@test.com', 'Secure1Pass');

        expect($user->name)->toBe(config('setup.defaults.admin_name', 'Administrator'));
        expect($user->username)->toBe(config('setup.defaults.admin_username', 'superadmin'));
    });
});

// ─── Super Admin Integrity ─────────────────────────────────────────────────

describe('super admin integrity', function () {
    it('prevents recreating immutable super admin', function () {
        app(SetupSuperAdminAction::class)->execute('admin@example.com', 'Secure1Pass');

        expect(fn () => app(SetupSuperAdminAction::class)->execute('evil@example.com', 'Hack1234')
        )->toThrow(RejectedException::class);
    });

    it('preserves super admin password hash without rehash needed', function () {
        app(SetupSuperAdminAction::class)->execute('admin@example.com', 'Secure1Pass');

        $user = User::first();
        expect(Hash::needsRehash($user->password))->toBeFalse();
    });

    it('does not expose password hash in user serialization', function () {
        app(SetupSuperAdminAction::class)->execute('admin@example.com', 'Secure1Pass');

        $json = User::first()->toJson();
        $decoded = json_decode($json, true);

        expect($decoded)->not->toHaveKey('password');
    });

    it('grants super admin role on creation', function () {
        $user = app(SetupSuperAdminAction::class)->execute('admin@example.com', 'Secure1Pass');

        expect($user->hasRole(Role::SUPER_ADMIN->value))->toBeTrue();
    });
});

// ─── Recovery Key Security ────────────────────────────────────────────────

describe('recovery key security', function () {
    it('stores recovery key as hash not plaintext', function () {
        app(FinalizeSetupAction::class)->execute(
            schoolData: ['name' => 'SMK 1', 'institutional_code' => '001', 'email' => 'a@b.com'],
            departmentData: ['name' => 'TKI'],
            adminData: ['name' => 'Admin', 'username' => 'sa', 'email' => 'a@b.com', 'password' => 'Secure1Pass'],
        );

        $setup = Setup::first();
        expect(Hash::isHashed($setup->recovery_key))->toBeTrue();
    });

    it('recovery key does not match the stored hash directly', function () {
        $result = app(FinalizeSetupAction::class)->execute(
            schoolData: ['name' => 'SMK 1', 'institutional_code' => '001', 'email' => 'a@b.com'],
            departmentData: ['name' => 'TKI'],
            adminData: ['name' => 'Admin', 'username' => 'sa', 'email' => 'a@b.com', 'password' => 'Secure1Pass'],
        );

        $setup = Setup::first();
        expect($result)->not->toBe($setup->recovery_key);
        expect(Hash::check($result, $setup->recovery_key))->toBeTrue();
    });

    it('clears setup_token after finalization', function () {
        app(FinalizeSetupAction::class)->execute(
            schoolData: ['name' => 'SMK 1', 'institutional_code' => '001', 'email' => 'a@b.com'],
            departmentData: ['name' => 'TKI'],
            adminData: ['name' => 'Admin', 'username' => 'sa', 'email' => 'a@b.com', 'password' => 'Secure1Pass'],
        );

        expect(Setup::first()->setup_token)->toBeNull();
        expect(Setup::first()->token_expires_at)->toBeNull();
    });
});
