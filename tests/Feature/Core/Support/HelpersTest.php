<?php

declare(strict_types=1);

namespace Tests\Feature\Core\Support;

use App\Settings\Models\Setting;
use App\Settings\Support\AppInfo;
use App\Settings\Support\Settings;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

uses(LazilyRefreshDatabase::class);

describe('setting() helper', function () {
    beforeEach(function () {
        Cache::flush();
        Settings::clearOverrides();
        AppInfo::clearCache();
        Settings::set(['setup.is_installed' => ['value' => true, 'group' => 'setup', 'type' => 'boolean']]);
    });

    it('returns Settings instance when called without key', function () {
        $result = setting();

        expect($result)->toBeInstanceOf(Settings::class);
    });

    it('returns default when key not found in database or config', function () {
        $result = setting('nonexistent_key', 'default_value');

        expect($result)->toBe('default_value');
    });

    it('returns value from database setting', function () {
        Setting::create(['key' => 'test_setting', 'value' => 'db_value', 'group' => 'test']);

        $result = setting('test_setting');

        expect($result)->toBe('db_value');
    });

    it('returns value from config when not in database', function () {
        Config::set('test_config_key', 'config_value');

        $result = setting('test_config_key');

        expect($result)->toBe('config_value');
    });

    it('database value takes precedence over config', function () {
        Setting::create(['key' => 'precedence_test', 'value' => 'db_wins', 'group' => 'test']);
        Config::set('precedence_test', 'config_loses');

        $result = setting('precedence_test');

        expect($result)->toBe('db_wins');
    });

    it('returns value from AppInfo mapping (app_name)', function () {
        $result = setting('app_name');

        expect($result)->toBeString();
    });

    it('returns value from AppInfo mapping (app_version)', function () {
        $result = setting('app_version');

        expect($result)->toBeString();
    });

    it('returns array when multiple keys provided', function () {
        Setting::create(['key' => 'multi_1', 'value' => 'value1', 'group' => 'test']);
        Setting::create(['key' => 'multi_2', 'value' => 'value2', 'group' => 'test']);

        $result = setting(['multi_1', 'multi_2']);

        expect($result)->toBeArray();
        expect($result['multi_1'])->toBe('value1');
        expect($result['multi_2'])->toBe('value2');
    });

    it('uses override when set', function () {
        Settings::override(['override_test' => 'overridden_value']);

        $result = setting('override_test', 'default');

        expect($result)->toBe('overridden_value');
    });

    it('override takes precedence over database', function () {
        Setting::create(['key' => 'override_db', 'value' => 'db_value', 'group' => 'test']);
        Settings::override(['override_db' => 'override_value']);

        $result = setting('override_db');

        expect($result)->toBe('override_value');
    });

    it('skipCache bypasses cache and reads fresh', function () {
        Setting::create(['key' => 'skip_cache_test', 'value' => 'original', 'group' => 'test']);
        $first = setting('skip_cache_test');

        Setting::where('key', 'skip_cache_test')->update(['value' => 'updated']);
        $cached = setting('skip_cache_test');
        $fresh = setting('skip_cache_test', null, true);

        expect($first)->toBe('original');
        expect($cached)->toBe('original');
        expect($fresh)->toBe('updated');
    });

    it('returns null default when key not found and no default provided', function () {
        $result = setting('truly_nonexistent');

        expect($result)->toBeNull();
    });
});

describe('brand() helper', function () {
    beforeEach(function () {
        Cache::flush();
        Settings::clearOverrides();
        AppInfo::clearCache();
    });

    it('returns name when config not set', function () {
        $result = brand('name');

        expect($result)->toBeString();
    });

    it('returns default logo when config not set', function () {
        $result = brand('logo');

        expect($result)->toBeString()->toContain('/logo.png');
    });

    it('returns default favicon when config not set', function () {
        $result = brand('favicon');

        expect($result)->toBeString()->toContain('/favicon.ico');
    });

    it('returns title fallback when config not set', function () {
        $result = brand('title');

        expect($result)->toBeString();
    });

    it('returns default colors when config not set', function () {
        $result = brand('colors');

        expect($result)->toBeArray();
        expect($result)->toHaveKeys(['primary', 'secondary', 'accent', 'base', 'content']);
    });

    it('returns version from AppInfo', function () {
        $result = brand('version');

        expect($result)->toBeString();
    });

    it('returns author name from AppInfo', function () {
        $result = brand('author_name');

        expect($result)->toBeString();
    });

    it('returns author email from AppInfo', function () {
        $result = brand('author_email');

        expect($result)->toBeString();
    });

    it('returns description from AppInfo', function () {
        $result = brand('description');

        expect($result)->toBeString();
    });

    it('returns license from AppInfo', function () {
        $result = brand('license');

        expect($result)->toBeString();
    });

    it('returns default for unknown key', function () {
        $result = brand('unknown_key', 'fallback');

        expect($result)->toBe('fallback');
    });
});

describe('app_info() helper', function () {
    beforeEach(function () {
        AppInfo::clearCache();
        Cache::flush();
        Settings::set(['setup.is_installed' => ['value' => true, 'group' => 'setup', 'type' => 'boolean']]);
    });

    it('returns all metadata when called without key', function () {
        $result = app_info();

        expect($result)->toBeArray();
        expect($result)->toHaveKeys(['name', 'version', 'description', 'license', 'author', 'support']);
    });

    it('returns specific metadata by key', function () {
        $result = app_info('name');

        expect($result)->toBeString();
    });

    it('returns version', function () {
        $result = app_info('version');

        expect($result)->toBeString();
    });

    it('returns author array', function () {
        $result = app_info('author');

        expect($result)->toBeArray();
    });

    it('returns default for unknown key', function () {
        $result = app_info('unknown_key', 'default_value');

        expect($result)->toBe('default_value');
    });

    it('returns null for unknown key without default', function () {
        $result = app_info('unknown_key');

        expect($result)->toBeNull();
    });
});

describe('Integration: helpers work together', function () {
    beforeEach(function () {
        Cache::flush();
        Settings::clearOverrides();
        AppInfo::clearCache();
        Settings::set(['setup.is_installed' => ['value' => true, 'group' => 'setup', 'type' => 'boolean']]);
    });

    it('brand() falls back to AppInfo when no settings exist', function () {
        $brandName = brand('name');
        $appName = setting('app_name');

        expect($brandName)->toBeString();
        expect($appName)->toBeString();
    });

    it('Brand reads from settings database when key is set', function () {
        Setting::create(['key' => 'name', 'value' => 'DB Brand', 'group' => 'branding']);

        $result = brand('name');

        expect($result)->toBe('DB Brand');
    });
});
