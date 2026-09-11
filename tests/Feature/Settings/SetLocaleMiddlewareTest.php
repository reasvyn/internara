<?php

declare(strict_types=1);

use App\Modules\Settings\Domain\Locale\Http\Middleware\SetLocaleMiddleware;
use App\Modules\Settings\Domain\Locale\Support\Locale;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Cookie;

uses(LazilyRefreshDatabase::class);

function localeRequest(?string $cookie): Request
{
    $request = Request::create('/', 'GET');
    if ($cookie !== null) {
        $request->cookies->set('locale', $cookie);
    }
    app()->instance('request', $request);

    return $request;
}

describe('52O1I: locale middleware and preference', function (): void {
    test('52O1I-FR-BRAND-015: middleware applies the resolved locale to the app', function (): void {
        $request = localeRequest('id');

        $response = app(SetLocaleMiddleware::class)->handle(
            $request,
            fn () => response('ok', 200),
        );

        expect(App::getLocale())->toBe('id')
            ->and($response->getStatusCode())->toBe(200);
    });

    test('52O1I-FR-BRAND-017: cookie wins over the stored default locale', function (): void {
        $this->seedSetting('default_locale', 'en', 'general', 'string');
        Cache::forget(config('cache-keys.settings_key').'default_locale');

        $request = localeRequest('id');

        app(SetLocaleMiddleware::class)->handle($request, fn () => response('ok', 200));

        expect(App::getLocale())->toBe('id');
    });

    test('52O1I-FR-BRAND-017: stored default applies when no cookie is present', function (): void {
        $this->seedSetting('default_locale', 'id', 'general', 'string');
        Cache::forget(config('cache-keys.settings_key').'default_locale');

        $request = localeRequest(null);

        app(SetLocaleMiddleware::class)->handle($request, fn () => response('ok', 200));

        expect(App::getLocale())->toBe('id');
    });

    test('52O1I-FR-BRAND-016: Locale set validates, queues the cookie and switches runtime', function (): void {
        expect(Locale::set('xx'))->toBeFalse();

        expect(Locale::set('id'))->toBeTrue()
            ->and(Cookie::queued('locale')?->getValue())->toBe('id')
            ->and(App::getLocale())->toBe('id');
    });

    test('52O1I-FR-BRAND-014: preference lives in a forever cookie, never in the database', function (): void {
        Locale::set('id');

        $queued = Cookie::queued('locale');

        expect($queued?->getValue())->toBe('id')
            ->and($queued?->getExpiresTime())->toBeGreaterThan(time() + 3600 * 24 * 30);

        $this->assertDatabaseMissing('settings', ['key' => 'locale']);
    });

    test('52O1I-FR-BRAND-013: exactly English and Indonesian are supported', function (): void {
        expect(Locale::keys())->toBe(['en', 'id'])
            ->and(Locale::isSupported('en'))->toBeTrue()
            ->and(Locale::isSupported('id'))->toBeTrue()
            ->and(Locale::isSupported('ms'))->toBeFalse();
    });

    test('2CF4Y-FR-MID-007: a locale switch survives across subsequent requests', function (): void {
        $first = localeRequest('id');
        app(SetLocaleMiddleware::class)->handle($first, fn () => response('ok', 200));
        expect(App::getLocale())->toBe('id');

        App::setLocale('en');

        $second = localeRequest('id');
        app(SetLocaleMiddleware::class)->handle($second, fn () => response('ok', 200));

        expect(App::getLocale())->toBe('id');
    });
});
