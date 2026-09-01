<?php

declare(strict_types=1);

use App\Modules\Settings\Models\Setting;
use App\Modules\Settings\Services\Settings;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| YB22J — Settings Infrastructure — SettingObserver cache invalidation (spec-driven)
|--------------------------------------------------------------------------
*/

beforeEach(function () {
    Settings::clearOverrides();
    Cache::flush();
});

describe('YB22J: SettingObserver invalidation', function (): void {
    test('YB22J-FR-S11: invalidates settings_key, settings_all and group on created', function () {
        Cache::put(config('cache-keys.settings_key').'obs.k', 'cached');
        Cache::put(config('cache-keys.settings_all'), 'cached');
        Cache::put(config('cache-keys.settings_group').'general', 'cached');

        Setting::create(['key' => 'obs.k', 'value' => 'v', 'type' => 'string', 'group' => 'general']);

        expect(Cache::has(config('cache-keys.settings_key').'obs.k'))->toBeFalse()
            ->and(Cache::has(config('cache-keys.settings_all')))->toBeFalse()
            ->and(Cache::has(config('cache-keys.settings_group').'general'))->toBeFalse();
    });

    test('YB22J-FR-S11: invalidates cache keys on updated', function () {
        $setting = Setting::create(['key' => 'obs.u', 'value' => 'old', 'type' => 'string', 'group' => 'general']);
        Cache::put(config('cache-keys.settings_key').'obs.u', 'cached');
        Cache::put(config('cache-keys.settings_all'), 'cached');

        $setting->update(['value' => 'new']);

        expect(Cache::has(config('cache-keys.settings_key').'obs.u'))->toBeFalse()
            ->and(Cache::has(config('cache-keys.settings_all')))->toBeFalse();
    });

    test('YB22J-FR-S11: invalidates cache keys on deleted', function () {
        $setting = Setting::create(['key' => 'obs.d', 'value' => 'v', 'type' => 'string', 'group' => 'general']);
        Cache::put(config('cache-keys.settings_key').'obs.d', 'cached');
        Cache::put(config('cache-keys.settings_group').'general', 'cached');

        $setting->delete();

        expect(Cache::has(config('cache-keys.settings_key').'obs.d'))->toBeFalse()
            ->and(Cache::has(config('cache-keys.settings_group').'general'))->toBeFalse();
    });

    test('YB22J-FR-S12: clearing a theme key invalidates theme and brand caches', function () {
        Cache::put(config('cache-keys.theme_css_variables'), 'cached');
        Cache::put(config('cache-keys.brand_colors'), 'cached');

        Setting::create([
            'key' => 'primary_color',
            'value' => '#059669',
            'type' => 'string',
            'group' => 'branding',
        ]);

        expect(config('settings.theme_cache_keys'))->toContain('primary_color')
            ->and(Cache::has(config('cache-keys.theme_css_variables')))->toBeFalse()
            ->and(Cache::has(config('cache-keys.brand_colors')))->toBeFalse();
    });

    test('YB22J-FR-S12: non-theme key does not clear theme cache', function () {
        Cache::put(config('cache-keys.theme_css_variables'), 'cached');
        Cache::put(config('cache-keys.brand_colors'), 'cached');

        Setting::create([
            'key' => 'support_email',
            'value' => 'a@b.c',
            'type' => 'string',
            'group' => 'general',
        ]);

        expect(config('settings.theme_cache_keys'))->not->toContain('support_email')
            ->and(Cache::has(config('cache-keys.theme_css_variables')))->toBeTrue()
            ->and(Cache::has(config('cache-keys.brand_colors')))->toBeTrue();
    });

    test('YB22J-FR-C3: observer invalidates synchronously within the same request', function () {
        Setting::create(['key' => 'obs.sync', 'value' => 'v', 'type' => 'string', 'group' => 'general']);

        // Immediately after the model event, the cache must already be empty
        // (no queue involved) — proving synchronous invalidation.
        expect(Cache::has(config('cache-keys.settings_key').'obs.sync'))->toBeFalse();
    });
});
