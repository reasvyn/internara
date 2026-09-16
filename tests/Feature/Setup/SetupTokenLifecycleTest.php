<?php

declare(strict_types=1);

use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\Setting\Models\Setting;
use App\Modules\Setup\Domain\Installation\Actions\GenerateSetupTokenAction;
use App\Modules\Setup\Domain\Installation\Actions\ValidateSetupTokenAction;
use App\Modules\Setup\Domain\Installation\Http\Middleware\ProtectSetupRouteMiddleware;
use App\Modules\Setup\Entities\SetupEntity;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Session;

uses(LazilyRefreshDatabase::class);

function instStoredTokenRaw(): ?string
{
    $row = Setting::where('key', 'setup.install_token')->first();

    return $row?->getRawOriginal('value');
}

describe('8NZAU: setup token lifecycle', function (): void {
    test('8NZAU-FR-INST-011, 8NZAU-NFR-INST-001: generated token is 64-char random, encrypted at rest, expiring in 60 minutes', function (): void {
        // tests/TestCase.php seeds setup.is_installed=true; installation specs need pre-install state.
        $this->seedSetting('setup.is_installed', false, 'setup', 'boolean');
        Cache::flush();

        $before = SetupEntity::get()->tokenVersion();
        $data = app(GenerateSetupTokenAction::class)->execute();
        Cache::flush();

        expect(strlen($data->plaintext))->toBe(64)
            ->and($data->expiresAt->isFuture())->toBeTrue()
            ->and(abs($data->expiresAt->diffInMinutes(now())))->toBeGreaterThanOrEqual(59);

        $raw = instStoredTokenRaw();
        expect($raw)->not->toBeNull()
            ->and($raw)->not->toBe($data->plaintext)
            ->and(Crypt::decryptString($raw))->toBe($data->plaintext);

        $state = SetupEntity::get();
        expect($state->hasStoredToken())->toBeTrue()
            ->and($state->tokenVersion())->toBe($before + 1);
    });

    test('8NZAU-FR-INST-012, 8NZAU-NFR-INST-001: validated token is single-use and replay is rejected', function (): void {
        // tests/TestCase.php seeds setup.is_installed=true; installation specs need pre-install state.
        $this->seedSetting('setup.is_installed', false, 'setup', 'boolean');
        Cache::flush();

        $data = app(GenerateSetupTokenAction::class)->execute();
        Cache::flush();

        app(ValidateSetupTokenAction::class)->execute($data->plaintext);
        Cache::flush();

        expect(SetupEntity::get()->hasStoredToken())->toBeFalse();
        expect(fn () => app(ValidateSetupTokenAction::class)->execute($data->plaintext))
            ->toThrow(RejectedException::class);
    });

    test('8NZAU-FR-INST-011: wrong, expired, and missing tokens are rejected with translatable messages', function (): void {
        app()->setLocale('en');
        // tests/TestCase.php seeds setup.is_installed=true; installation specs need pre-install state.
        $this->seedSetting('setup.is_installed', false, 'setup', 'boolean');
        Cache::flush();

        $data = app(GenerateSetupTokenAction::class)->execute();
        Cache::flush();

        try {
            app(ValidateSetupTokenAction::class)->execute('wrong-token-value-that-matches-nothing');
            $this->fail('Expected a RejectedException for a mismatched token.');
        } catch (RejectedException $e) {
            expect($e->getMessage())->toBe(__('setup.token_mismatch'));
        }

        Cache::flush();
        expect(SetupEntity::get()->hasStoredToken())->toBeTrue();

        $this->seedSetting('setup.token_expires_at', now()->subMinutes(5)->toIso8601String(), 'setup', 'datetime');
        Cache::flush();

        try {
            app(ValidateSetupTokenAction::class)->execute($data->plaintext);
            $this->fail('Expected a RejectedException for an expired token.');
        } catch (RejectedException $e) {
            expect($e->getMessage())->toBe(__('setup.token_expired'));
        }

        Setting::where('key', 'setup.install_token')->delete();
        Cache::flush();

        try {
            app(ValidateSetupTokenAction::class)->execute($data->plaintext);
            $this->fail('Expected a RejectedException for a missing token.');
        } catch (RejectedException $e) {
            expect($e->getMessage())->toBe(__('setup.token_missing'));
        }
    });

    test('8NZAU-FR-INST-014: token generation is safe to re-run and kills the previous token', function (): void {
        // tests/TestCase.php seeds setup.is_installed=true; installation specs need pre-install state.
        $this->seedSetting('setup.is_installed', false, 'setup', 'boolean');
        Cache::flush();

        $first = app(GenerateSetupTokenAction::class)->execute();
        Cache::flush();
        $versionAfterFirst = SetupEntity::get()->tokenVersion();

        $second = app(GenerateSetupTokenAction::class)->execute();
        Cache::flush();

        expect($second->plaintext)->not->toBe($first->plaintext);
        expect(SetupEntity::get()->tokenVersion())->toBe($versionAfterFirst + 1);
        expect(Crypt::decryptString(instStoredTokenRaw()))->toBe($second->plaintext);

        expect(fn () => app(ValidateSetupTokenAction::class)->execute($first->plaintext))
            ->toThrow(RejectedException::class);

        app(ValidateSetupTokenAction::class)->execute($second->plaintext);
        Cache::flush();
        expect(SetupEntity::get()->hasStoredToken())->toBeFalse();
    });

    test('8NZAU-FR-INST-019, 8NZAU-UC-INST-003: setup:reset-token rotates the token and the fresh URL continues in the browser', function (): void {
        // tests/TestCase.php seeds setup.is_installed=true; installation specs need pre-install state.
        $this->seedSetting('setup.is_installed', false, 'setup', 'boolean');
        Cache::flush();

        $old = app(GenerateSetupTokenAction::class)->execute();
        Cache::flush();

        $exit = Artisan::call('setup:reset-token');

        expect($exit)->toBe(0);
        expect(Artisan::output())->toContain('setup_token=');
        Cache::flush();

        expect(fn () => app(ValidateSetupTokenAction::class)->execute($old->plaintext))
            ->toThrow(RejectedException::class);

        $fresh = Crypt::decryptString(instStoredTokenRaw());
        expect($fresh)->not->toBe($old->plaintext);

        $response = app(ProtectSetupRouteMiddleware::class)->handle(
            Request::create('/setup', 'GET', ['setup_token' => $fresh]),
            fn () => response('wizard', 200),
        );

        expect($response->getStatusCode())->toBe(200)
            ->and($response->getContent())->toBe('wizard');
    });

    test('8NZAU-FR-INST-019: setup:reset-token is refused once the system is installed', function (): void {
        Cache::flush();
        $before = SetupEntity::get()->tokenVersion();

        $exit = Artisan::call('setup:reset-token');

        expect($exit)->toBe(1);
        Cache::flush();
        expect(SetupEntity::get()->tokenVersion())->toBe($before);
    });

    test('8NZAU-FR-INST-012, 8NZAU-FR-INST-021: rotating the token invalidates sessions minted from the old version', function (): void {
        // tests/TestCase.php seeds setup.is_installed=true; installation specs need pre-install state.
        $this->seedSetting('setup.is_installed', false, 'setup', 'boolean');
        Cache::flush();

        $first = app(GenerateSetupTokenAction::class)->execute();
        Cache::flush();

        $admitted = app(ProtectSetupRouteMiddleware::class)->handle(
            Request::create('/setup', 'GET', ['setup_token' => $first->plaintext]),
            fn () => response('wizard', 200),
        );
        expect($admitted->getContent())->toBe('wizard');
        expect(Session::get('setup.authorized'))->toBeTrue();

        app(GenerateSetupTokenAction::class)->execute();
        Cache::flush();

        $denied = app(ProtectSetupRouteMiddleware::class)->handle(
            Request::create('/setup', 'GET'),
            fn () => response('wizard', 200),
        );

        expect($denied->getContent())->not->toBe('wizard');
        expect($denied->getOriginalContent()->getName())->toBe('setup.enter-code');
    });

    test('8NZAU-FR-INST-021, 8NZAU-NFR-INST-002: token validation establishes a versioned session with a regenerated id', function (): void {
        // tests/TestCase.php seeds setup.is_installed=true; installation specs need pre-install state.
        $this->seedSetting('setup.is_installed', false, 'setup', 'boolean');
        Cache::flush();

        $data = app(GenerateSetupTokenAction::class)->execute();
        Cache::flush();
        $sessionIdBefore = Session::getId();

        $response = app(ProtectSetupRouteMiddleware::class)->handle(
            Request::create('/setup', 'GET', ['setup_token' => $data->plaintext]),
            fn () => response('wizard', 200),
        );

        expect($response->getContent())->toBe('wizard');
        expect(Session::get('setup.authorized'))->toBeTrue();
        expect((int) Session::get('setup.token_version'))->toBe(SetupEntity::get()->tokenVersion());
        expect(Session::getId())->not->toBe($sessionIdBefore);
    });
});
