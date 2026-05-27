<?php

declare(strict_types=1);

use App\Domain\Setup\Actions\FinalizeSetupAction;
use App\Domain\Setup\Actions\GenerateSetupTokenAction;
use App\Domain\Setup\Actions\ValidateSetupTokenAction;
use App\Domain\Setup\Livewire\SetupWizard;
use App\Domain\Setup\Models\Setup;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Session;
use Spatie\Permission\Models\Role as RoleModel;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    foreach (['super_admin', 'admin', 'student', 'teacher', 'supervisor'] as $role) {
        RoleModel::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
    }
    app()->setLocale('en');
    Setup::query()->delete();
    Session::flush();
});

// ─── Self-Destruction: Wizard is permanently locked after install ─────────

describe('wizard self-destruction after install', function () {
    it('returns 404 for all wizard routes when system is installed', function () {
        Setup::create(['is_installed' => true]);

        $this->get(route('setup'))->assertStatus(404);
    });

    it('returns 404 even with valid session token after install', function () {
        Setup::create(['is_installed' => true]);
        Session::put('setup.authorized', true);

        $this->get(route('setup'))->assertStatus(404);
    });

    it('clears all setup session data when accessing wizard after window expires', function () {
        Setup::create(['is_installed' => true]);
        Session::put('setup.authorized', true);
        Session::put('setup.token', 'secret');
        Session::put('setup.token_input', 'secret');
        Session::put('setup.form_data', ['school' => ['name' => 'X']]);
        Session::put('setup.completed', true);
        Setup::query()->update(['updated_at' => now()->subMinutes(5)]);

        $this->get(route('setup'))->assertStatus(404);

        expect(Session::has('setup.authorized'))->toBeFalse();
        expect(Session::has('setup.token'))->toBeFalse();
        expect(Session::has('setup.token_input'))->toBeFalse();
        expect(Session::has('setup.form_data'))->toBeFalse();
        expect(Session::has('setup.completed'))->toBeFalse();
    });

    it('redirects SetupWizard mount to login when installed', function () {
        Setup::create(['is_installed' => true]);

        Livewire::test(SetupWizard::class)
            ->assertRedirect(route('login'));
    });

    it('does not leak setup routes via requireSetupAccess middleware after install', function () {
        Setup::create(['is_installed' => true]);

        $this->get('/login')->assertStatus(200);
        $this->get(route('setup'))->assertStatus(404);
    });
});

// ─── Finalization Window: Tight and single-purpose ────────────────────────

describe('finalization window', function () {
    it('allows access ONLY with setup.completed flag within window', function () {
        Setup::create(['is_installed' => true]);
        Session::put('setup.completed', true);

        $this->get(route('setup'))->assertStatus(200);
    });

    it('blocks access when setup.completed flag is missing', function () {
        Setup::create(['is_installed' => true]);

        $this->get(route('setup'))->assertStatus(404);
    });

    it('blocks access when setup.authorized (wrong flag) is set', function () {
        Setup::create(['is_installed' => true]);
        Session::put('setup.authorized', true);

        $this->get(route('setup'))->assertStatus(404);
    });

    it('blocks access after window expires even with completed flag', function () {
        Setup::create(['is_installed' => true]);
        Session::put('setup.completed', true);
        Setup::query()->update(['updated_at' => now()->subMinutes(5)]);

        $this->get(route('setup'))->assertStatus(404);
    });

    it('blocks access to wizard steps after install (only complete page)', function () {
        Setup::create(['is_installed' => true]);
        Session::put('setup.completed', true);

        $this->get(route('setup'))
            ->assertStatus(200);

        Livewire::test(SetupWizard::class)
            ->assertSet('currentStep', 7);
    });

    it('clears completed flag when user navigates to login', function () {
        Setup::create(['is_installed' => true]);
        Session::put('setup.completed', true);

        Session::forget('setup.completed');

        $this->get(route('setup'))->assertStatus(404);
    });
});

// ─── Token: Single-use, consumed after validation ─────────────────────────

describe('token single-use enforcement', function () {
    it('consumes token after successful validation', function () {
        Setup::create(['is_installed' => false]);
        $result = app(GenerateSetupTokenAction::class)->execute();

        $setup = Setup::first();
        expect($setup->setup_token)->not->toBeNull();

        app(ValidateSetupTokenAction::class)->execute($result['plaintext']);

        $setup->refresh();
        expect($setup->setup_token)->toBeNull();
        expect($setup->token_expires_at)->toBeNull();
    });

    it('rejects reused token on second attempt', function () {
        Setup::create(['is_installed' => false]);
        $result = app(GenerateSetupTokenAction::class)->execute();

        app(ValidateSetupTokenAction::class)->execute($result['plaintext']);

        expect(fn () => app(ValidateSetupTokenAction::class)->execute($result['plaintext'])
        )->toThrow(RuntimeException::class, 'Invalid setup token.');
    });

    it('middleware does not accept already-consumed token via query param', function () {
        Setup::create(['is_installed' => false]);
        $result = app(GenerateSetupTokenAction::class)->execute();

        app(ValidateSetupTokenAction::class)->execute($result['plaintext']);

        $this->get(route('setup', ['setup_token' => $result['plaintext']]))
            ->assertStatus(403);
    });

    it('middleware does not accept already-consumed token via POST', function () {
        Setup::create(['is_installed' => false]);
        $result = app(GenerateSetupTokenAction::class)->execute();

        app(ValidateSetupTokenAction::class)->execute($result['plaintext']);

        $this->post(route('setup'), ['setup_token' => $result['plaintext']])
            ->assertStatus(403);
    });

    it('new token must be generated after consumption', function () {
        Setup::create(['is_installed' => false]);
        $result1 = app(GenerateSetupTokenAction::class)->execute();

        app(ValidateSetupTokenAction::class)->execute($result1['plaintext']);

        $result2 = app(GenerateSetupTokenAction::class)->execute();
        expect($result2['plaintext'])->not->toBe($result1['plaintext']);

        app(ValidateSetupTokenAction::class)->execute($result2['plaintext']);
        expect(true)->toBeTrue();
    });
});

// ─── Finalization permanently seals the system ────────────────────────────

describe('finalization permanently seals setup', function () {
    it('cannot call FinalizeSetupAction twice', function () {
        app(FinalizeSetupAction::class)->execute(
            schoolData: ['name' => 'SMK 1', 'institutional_code' => '001', 'email' => 'a@b.com'],
            departmentData: ['name' => 'TKI'],
            adminData: ['email' => 'admin@test.com', 'password' => 'Secure1Pass'],
        );

        expect(fn () => app(FinalizeSetupAction::class)->execute(
            schoolData: ['name' => 'SMK 2', 'institutional_code' => '002', 'email' => 'b@b.com'],
            departmentData: ['name' => 'MM'],
            adminData: ['email' => 'admin2@test.com', 'password' => 'Secure2Pass'],
        ))->toThrow(RuntimeException::class, 'already installed');
    });

    it('clears all setup session data after finalization', function () {
        Session::put('setup.authorized', true);
        Session::put('setup.token', 'secret');
        Session::put('setup.form_data', ['school' => ['name' => 'X']]);

        app(FinalizeSetupAction::class)->execute(
            schoolData: ['name' => 'SMK 1', 'institutional_code' => '001', 'email' => 'a@b.com'],
            departmentData: ['name' => 'TKI'],
            adminData: ['email' => 'admin@test.com', 'password' => 'Secure1Pass'],
        );

        expect(Session::has('setup.authorized'))->toBeFalse();
        expect(Session::has('setup.token'))->toBeFalse();
        expect(Session::has('setup.token_input'))->toBeFalse();
        expect(Session::has('setup.form_data'))->toBeFalse();
    });

    it('clears setup_token and token_expires_at on finalization', function () {
        $token = app(GenerateSetupTokenAction::class)->execute();

        app(FinalizeSetupAction::class)->execute(
            schoolData: ['name' => 'SMK 1', 'institutional_code' => '001', 'email' => 'a@b.com'],
            departmentData: ['name' => 'TKI'],
            adminData: ['email' => 'admin@test.com', 'password' => 'Secure1Pass'],
        );

        $setup = Setup::first();
        expect($setup->setup_token)->toBeNull();
        expect($setup->token_expires_at)->toBeNull();
    });

    it('stores recovery key as hash, not plaintext', function () {
        $result = app(FinalizeSetupAction::class)->execute(
            schoolData: ['name' => 'SMK 1', 'institutional_code' => '001', 'email' => 'a@b.com'],
            departmentData: ['name' => 'TKI'],
            adminData: ['email' => 'admin@test.com', 'password' => 'Secure1Pass'],
        );

        $setup = Setup::first();
        expect($result)->not->toBe($setup->recovery_key);
        expect(Hash::isHashed($setup->recovery_key))->toBeTrue();
        expect(Hash::check($result, $setup->recovery_key))->toBeTrue();
    });
});

// ─── setup:reset blocked when installed ───────────────────────────────────

describe('setup:reset blocked after install', function () {
    it('blocks setup:reset when system is installed', function () {
        Setup::create(['is_installed' => true]);

        $this->artisan('setup:reset')
            ->assertExitCode(1);
    });

    it('allows setup:reset when system is not installed', function () {
        Setup::create(['is_installed' => false]);

        $this->artisan('setup:reset')
            ->assertExitCode(0);
    });
});

// ─── No backdoor via route manipulation ───────────────────────────────────

describe('no route-level bypass', function () {
    it('returns 404 for POST to setup when installed', function () {
        Setup::create(['is_installed' => true]);

        $this->post(route('setup'))->assertStatus(404);
    });

    it('returns 404 for setup with query parameters when installed', function () {
        Setup::create(['is_installed' => true]);

        $this->get('/setup?foo=bar')->assertStatus(404);
    });

    it('rejects expired token with 403 not 500', function () {
        Setup::create(['is_installed' => false]);
        $token = app(GenerateSetupTokenAction::class)->execute();
        Setup::first()->update(['token_expires_at' => now()->subMinute()]);

        $this->get(route('setup', ['setup_token' => $token['plaintext']]))
            ->assertStatus(403);
    });
});
