<?php

declare(strict_types=1);

use App\Settings\Data\SettingData;
use App\Settings\Events\SettingUpdated;
use App\Settings\Listeners\InvalidateSettingsCache;
use App\Settings\Models\Setting;
use App\Settings\Services\Settings;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Cache::flush();
});

test('event is dispatched when setting is created via Settings::set()', function () {
    Event::fake([SettingUpdated::class]);

    Settings::set([
        'test.event_key' => ['value' => 'test', 'group' => 'test', 'type' => 'string'],
    ]);

    Event::assertDispatched(SettingUpdated::class, function (SettingUpdated $event) {
        return $event->setting->key === 'test.event_key'
            && $event->wasRecentlyCreated === true;
    });
});

test('listener invalidates cache on setting update', function () {
    $key = 'site.name';
    Setting::create(['key' => $key, 'value' => 'Original', 'group' => 'general', 'type' => 'string']);

    Cache::put(config('cache-keys.settings_key').$key, 'Original', 3600);
    expect(Cache::get(config('cache-keys.settings_key').$key))->toBe('Original');

    $listener = app(InvalidateSettingsCache::class);
    $listener->handle(new SettingUpdated(
        setting: new SettingData(key: $key, value: 'Updated', group: 'general'),
        wasRecentlyCreated: false,
    ));

    expect(Cache::get(config('cache-keys.settings_key').$key))->toBeNull();
    expect(Cache::get(config('cache-keys.settings_all')))->toBeNull();
});

test('listener invalidates theme cache for color keys', function () {
    $listener = app(InvalidateSettingsCache::class);

    Cache::put(config('cache-keys.theme_css_variables'), 'cached', 3600);
    Cache::put(config('cache-keys.brand_colors'), 'cached', 3600);

    $listener->handle(new SettingUpdated(
        setting: new SettingData(key: 'primary_color', value: '#ff0000', group: 'theme'),
        wasRecentlyCreated: false,
    ));

    expect(Cache::get(config('cache-keys.theme_css_variables')))->toBeNull();
    expect(Cache::get(config('cache-keys.brand_colors')))->toBeNull();
});

test('listener does not invalidate unrelated cache', function () {
    $listener = app(InvalidateSettingsCache::class);

    Cache::put(config('cache-keys.health_check'), 'healthy', 3600);

    $listener->handle(new SettingUpdated(
        setting: new SettingData(key: 'unrelated.key', value: 'val'),
        wasRecentlyCreated: false,
    ));

    expect(Cache::get(config('cache-keys.health_check')))->toBe('healthy');
});
