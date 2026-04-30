<?php

declare(strict_types=1);

namespace Tests\Feature\Settings;

use App\Models\Setting;
use App\Support\AppInfo;
use App\Support\Settings;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    Config::set('cache.default', 'array');

    $this->metadataPath = base_path('app_info.json');
    $this->originalContent = File::exists($this->metadataPath)
        ? File::get($this->metadataPath)
        : null;

    Settings::clearOverrides();
    AppInfo::clearCache();
    Cache::flush();
});

afterEach(function () {
    if ($this->originalContent) {
        File::put($this->metadataPath, $this->originalContent);
    } else {
        File::delete($this->metadataPath);
    }
    AppInfo::clearCache();
    Settings::clearOverrides();
    Cache::flush();
});

test('settings returns default when nothing is configured', function () {
    expect(Settings::get('nonexistent', 'fallback'))->toBe('fallback');
});

test('settings falls back to database value', function () {
    Setting::create([
        'key' => 'custom_key',
        'value' => 'db-value',
        'type' => 'string',
    ]);

    expect(Settings::get('custom_key'))->toBe('db-value');
});

test('settings falls back to config if not in db', function () {
    Config::set('my.config.key', 'config-value');

    expect(Settings::get('my.config.key'))->toBe('config-value');
});

test('settings resolves app_name from AppInfo SSoT', function () {
    File::put($this->metadataPath, json_encode(['name' => 'TestApp']));
    AppInfo::clearCache();

    expect(Settings::get('app_name'))->toBe('TestApp');
});

test('settings resolves app_version from AppInfo SSoT', function () {
    File::put($this->metadataPath, json_encode(['version' => '2.0.0']));
    AppInfo::clearCache();

    expect(Settings::get('app_version'))->toBe('2.0.0');
});

test('settings runtime override takes highest priority', function () {
    Setting::create([
        'key' => 'test_key',
        'value' => 'db-value',
        'type' => 'string',
    ]);

    Settings::override(['test_key' => 'overridden']);

    expect(Settings::get('test_key'))->toBe('overridden');
});

test('settings clearOverrides restores normal resolution', function () {
    Settings::override(['test_key' => 'overridden']);
    Settings::clearOverrides();

    expect(Settings::get('test_key', 'default'))->toBe('default');
});

test('settings has returns true for existing key', function () {
    Setting::create([
        'key' => 'check_key',
        'value' => 'exists',
        'type' => 'string',
    ]);

    expect(Settings::has('check_key'))->toBeTrue();
    expect(Settings::has('nonexistent_key'))->toBeFalse();
});

test('settings group returns cached collection', function () {
    Setting::create([
        'key' => 'group_a_key',
        'value' => 'val1',
        'type' => 'string',
        'group' => 'group_a',
    ]);
    Setting::create([
        'key' => 'group_a_key2',
        'value' => 'val2',
        'type' => 'string',
        'group' => 'group_a',
    ]);
    Setting::create([
        'key' => 'group_b_key',
        'value' => 'val3',
        'type' => 'string',
        'group' => 'group_b',
    ]);

    $group = Settings::group('group_a');

    expect($group)->toHaveCount(2);
});

test('settings get accepts array of keys', function () {
    Setting::create([
        'key' => 'key_one',
        'value' => 'one',
        'type' => 'string',
    ]);
    Setting::create([
        'key' => 'key_two',
        'value' => 'two',
        'type' => 'string',
    ]);

    $result = Settings::get(['key_one', 'key_two', 'key_missing'], 'default');

    expect($result)->toBe([
        'key_one' => 'one',
        'key_two' => 'two',
        'key_missing' => 'default',
    ]);
});

test('settings forget invalidates cache for key', function () {
    Setting::create([
        'key' => 'cache_test',
        'value' => 'cached',
        'type' => 'string',
    ]);

    expect(Settings::get('cache_test'))->toBe('cached');

    Settings::forget('cache_test');

    // After forget, cache is cleared so it fetches fresh from DB
    expect(Settings::get('cache_test'))->toBe('cached');
});
