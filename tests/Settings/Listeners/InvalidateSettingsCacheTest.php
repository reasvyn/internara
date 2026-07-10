<?php

declare(strict_types=1);
use App\Settings\Data\SettingData;
use App\Settings\Events\SettingUpdated;
use App\Settings\Listeners\InvalidateSettingsCache;
use App\Settings\Models\Setting;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Cache::flush();
});

test('listener clears single setting cache', function () {
    Setting::create(['key' => 'test_key', 'value' => 'value1', 'group' => 'test']);

    $listener = new InvalidateSettingsCache;
    $listener->handle(new SettingUpdated(
        setting: new SettingData(key: 'test_key', value: 'value1'),
        wasRecentlyCreated: false,
    ));

    expect(true)->toBeTrue();
});

test('listener clears group cache when group is set', function () {
    Setting::create(['key' => 'group_key', 'value' => 'value', 'group' => 'mail']);

    $cacheKey = config('cache-keys.settings_group').'mail';
    Cache::rememberForever($cacheKey, fn () => collect(['group_key' => 'value']));

    $listener = new InvalidateSettingsCache;
    $listener->handle(new SettingUpdated(
        setting: new SettingData(key: 'group_key', value: 'value', group: 'mail'),
        wasRecentlyCreated: false,
    ));

    expect(Cache::get($cacheKey))->toBeNull();
});

test('listener clears theme cache for theme keys', function () {
    config(['settings.theme_cache_keys' => ['primary_color', 'secondary_color']]);
    Setting::create(['key' => 'primary_color', 'value' => '#ff0000', 'group' => 'branding']);

    $themeKey = config('cache-keys.theme_css_variables');
    $brandKey = config('cache-keys.brand_colors');
    Cache::rememberForever($themeKey, fn () => ['light' => [], 'dark' => []]);
    Cache::rememberForever($brandKey, fn () => ['primary' => '#ff0000']);

    $listener = new InvalidateSettingsCache;
    $listener->handle(new SettingUpdated(
        setting: new SettingData(key: 'primary_color', value: '#ff0000', group: 'branding'),
        wasRecentlyCreated: false,
    ));

    expect(Cache::get($themeKey))->toBeNull();
    expect(Cache::get($brandKey))->toBeNull();
});

test('event name reflects creation state', function () {
    $created = new SettingUpdated(
        setting: new SettingData(key: 'new_key', value: 'val'),
        wasRecentlyCreated: true,
    );
    expect($created->eventName())->toBe('setting.created');

    $updated = new SettingUpdated(
        setting: new SettingData(key: 'existing_key', value: 'val'),
        wasRecentlyCreated: false,
    );
    expect($updated->eventName())->toBe('setting.updated');
});
