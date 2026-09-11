<?php

declare(strict_types=1);

use App\Modules\Setup\Domain\Installation\Http\Middleware\ProtectSetupRouteMiddleware;
use App\Modules\Setup\Domain\Installation\Http\Middleware\RequireSetupAccessMiddleware;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpKernel\Exception\HttpException;

uses(LazilyRefreshDatabase::class);

function markSetupInstalled(bool $installed): void
{
    Cache::put(config('cache-keys.setup_installed'), $installed);
}

function flushSetupCaches(): void
{
    Cache::forget(config('cache-keys.settings_key').'setup.is_installed');
    Cache::forget(config('cache-keys.setup_installed'));
}

describe('8NZAU and 2CF4Y: setup access middleware', function (): void {
    test('8NZAU-FR-INST-020: uninstalled instances redirect to the setup route', function (): void {
        markSetupInstalled(false);

        $response = app(RequireSetupAccessMiddleware::class)->handle(
            Request::create('/dashboard', 'GET'),
            fn () => response('ok', 200),
        );

        expect($response->isRedirect(route('setup')))->toBeTrue();
    });

    test('2CF4Y-FR-MID-009: setup path stays reachable while uninstalled', function (): void {
        markSetupInstalled(false);

        $response = app(RequireSetupAccessMiddleware::class)->handle(
            Request::create('/setup', 'GET'),
            fn () => response('wizard', 200),
        );

        expect($response->getStatusCode())->toBe(200)
            ->and($response->getContent())->toBe('wizard');
    });

    test('2CF4Y-FR-MID-009: installed instances pass straight through', function (): void {
        markSetupInstalled(true);

        $response = app(RequireSetupAccessMiddleware::class)->handle(
            Request::create('/dashboard', 'GET'),
            fn () => response('app', 200),
        );

        expect($response->getStatusCode())->toBe(200)
            ->and($response->getContent())->toBe('app');
    });

    test('2CF4Y-FR-MID-008: setup routes return 404 once installed', function (): void {
        $this->seedSetting('setup.is_installed', true, 'setup', 'boolean');
        flushSetupCaches();

        try {
            app(ProtectSetupRouteMiddleware::class)->handle(
                Request::create('/setup', 'GET'),
                fn () => response('wizard', 200),
            );

            $this->fail('Expected a 404 abort on setup routes after installation.');
        } catch (HttpException $e) {
            expect($e->getStatusCode())->toBe(404);
        }
    });

    test('2CF4Y-FR-MID-009: missing token renders the code-entry screen', function (): void {
        $this->seedSetting('setup.is_installed', false, 'setup', 'boolean');
        flushSetupCaches();

        $response = app(ProtectSetupRouteMiddleware::class)->handle(
            Request::create('/setup', 'GET'),
            fn () => response('wizard', 200),
        );

        expect($response->getStatusCode())->toBe(200)
            ->and($response->getOriginalContent()->getName())->toBe('setup.enter-code');
    });

    test('2CF4Y-FR-MID-009: versioned session authorization admits the wizard', function (): void {
        $this->seedSetting('setup.is_installed', false, 'setup', 'boolean');
        flushSetupCaches();
        Session::put('setup.authorized', true);
        Session::put('setup.token_version', 0);

        $response = app(ProtectSetupRouteMiddleware::class)->handle(
            Request::create('/setup', 'GET'),
            fn () => response('wizard', 200),
        );

        expect($response->getStatusCode())->toBe(200)
            ->and($response->getContent())->toBe('wizard');
    });

    test('8NZAU-FR-INST-013: repeated bad tokens hit the rate limit', function (): void {
        $this->seedSetting('setup.is_installed', false, 'setup', 'boolean');
        flushSetupCaches();
        config()->set('setup.security.rate_limit_attempts', 1);
        config()->set('setup.security.rate_limit_decay_seconds', 60);

        $attempt = function () {
            $request = Request::create('/setup?setup_token=bogus', 'GET');
            $request->headers->set('Accept', 'application/json');

            return $request;
        };

        try {
            app(ProtectSetupRouteMiddleware::class)->handle(
                $attempt(),
                fn () => response('wizard', 200),
            );
        } catch (HttpException) {
        }

        $limited = app(ProtectSetupRouteMiddleware::class)->handle(
            $attempt(),
            fn () => response('wizard', 200),
        );

        expect($limited->getStatusCode())->toBe(429);
    });
});
