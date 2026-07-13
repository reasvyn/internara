<?php

declare(strict_types=1);

use App\Settings\Models\Setting;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Cache::flush();
});

test('creating a setting invalidates settings cache', function () {
    $cacheKey = config('cache-keys.settings_all');
    Cache::put($cacheKey, 'stale', 3600);

    Setting::create(['key' => 'test.observer_create', 'value' => 'new', 'group' => 'test']);

    expect(Cache::get($cacheKey))->toBeNull();
});

test('updating a setting invalidates its cache key and group cache', function () {
    $setting = Setting::create(['key' => 'test.observer_update', 'value' => 'old', 'group' => 'test']);

    $keyCache = config('cache-keys.settings_key') . 'test.observer_update';
    $groupCache = config('cache-keys.settings_group') . 'test';
    $allCache = config('cache-keys.settings_all');

    Cache::put($keyCache, 'old', 3600);
    Cache::put($groupCache, 'stale', 3600);
    Cache::put($allCache, 'stale', 3600);

    $setting->update(['value' => 'new']);

    expect(Cache::get($keyCache))->toBeNull();
    expect(Cache::get($groupCache))->toBeNull();
    expect(Cache::get($allCache))->toBeNull();
});

test('deleting a setting invalidates its cache key and group cache', function () {
    $setting = Setting::create(['key' => 'test.observer_delete', 'value' => 'val', 'group' => 'test']);

    $keyCache = config('cache-keys.settings_key') . 'test.observer_delete';
    $groupCache = config('cache-keys.settings_group') . 'test';
    $allCache = config('cache-keys.settings_all');

    Cache::put($keyCache, 'stale', 3600);
    Cache::put($groupCache, 'stale', 3600);
    Cache::put($allCache, 'stale', 3600);

    $setting->delete();

    expect(Cache::get($keyCache))->toBeNull();
    expect(Cache::get($groupCache))->toBeNull();
    expect(Cache::get($allCache))->toBeNull();
});

test('theme cache is invalidated for theme cache keys', function () {
    config(['settings.theme_cache_keys' => ['primary_color', 'secondary_color']]);

    $themeKey = config('cache-keys.theme_css_variables');
    $brandKey = config('cache-keys.brand_colors');
    Cache::put($themeKey, 'cached', 3600);
    Cache::put($brandKey, 'cached', 3600);

    Setting::create(['key' => 'primary_color', 'value' => '#ff0000', 'group' => 'branding']);

    expect(Cache::get($themeKey))->toBeNull();
    expect(Cache::get($brandKey))->toBeNull();
});

test('unrelated cache is not affected', function () {
    Cache::put(config('cache-keys.health_check'), 'healthy', 3600);

    Setting::create(['key' => 'unrelated.key', 'value' => 'val']);

    expect(Cache::get(config('cache-keys.health_check')))->toBe('healthy');
});
