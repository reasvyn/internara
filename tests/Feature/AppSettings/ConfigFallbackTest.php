<?php

declare(strict_types=1);

use App\Models\Setting;
use App\Support\AppInfo;
use App\Support\Settings;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Settings::clearOverrides();
    Cache::clear();
    AppInfo::clearCache();
});

describe('config fallback tier', function () {
    it('falls back to Laravel config when no database setting exists', function () {
        $value = Settings::get('app.name');

        expect($value)->toBe(config('app.name'));
    });

    it('resolves nested config keys via dot notation', function () {
        $value = Settings::get('app.debug');

        expect($value)->toBe(config('app.debug'));
    });

    it('uses config as fallback after database miss', function () {
        expect(Setting::byKey('app.timezone')->exists())->toBeFalse();

        $value = Settings::get('app.timezone');

        expect($value)->toBe(config('app.timezone'));
    });

    it('returns default when config key does not exist either', function () {
        $value = Settings::get('completely.missing.key', 'custom_default');

        expect($value)->toBe('custom_default');
    });
});

describe('database overrides config', function () {
    it('prefers database value over config when both exist', function () {
        Setting::factory()->create(['key' => 'app.debug', 'value' => 'db_override']);

        $value = Settings::get('app.debug');

        expect($value)->toBe('db_override');
    });

    it('prefers database value over config for nested keys', function () {
        Setting::factory()->create(['key' => 'app.name', 'value' => 'DB Name']);

        $value = Settings::get('app.name');

        expect($value)->toBe('DB Name');
    });
});

describe('runtime overrides override all tiers', function () {
    it('overrides config values', function () {
        Settings::override(['app.name' => 'Override Name']);

        $value = Settings::get('app.name');

        expect($value)->toBe('Override Name');
    });

    it('overrides database values', function () {
        Setting::factory()->create(['key' => 'theme', 'value' => 'dark']);

        Settings::override(['theme' => 'light']);

        expect(Settings::get('theme'))->toBe('light');
    });

    it('overrides AppInfo mapped keys', function () {
        Settings::override(['app_name' => 'Override App']);

        $value = Settings::get('app_name');

        expect($value)->toBe('Override App');
    });
});

describe('AppInfo mapped keys skip config tier', function () {
    it('resolves app_name from AppInfo not config', function () {
        $value = Settings::get('app_name');

        expect($value)->toBe(AppInfo::get('name'));
    });

    it('resolves app_version from AppInfo', function () {
        $value = Settings::get('app_version');

        expect($value)->toBe(AppInfo::get('version'));
    });

    it('resolves app_author from AppInfo', function () {
        $value = Settings::get('app_author');

        expect($value)->toBe(AppInfo::get('author.name'));
    });

    it('resolves app_license from AppInfo', function () {
        $value = Settings::get('app_license');

        expect($value)->toBe(AppInfo::get('license'));
    });

    it('resolves app_support from AppInfo', function () {
        $value = Settings::get('app_support');

        expect($value)->toBe(AppInfo::get('support'));
    });
});

describe('resolution chain priority', function () {
    it('follows correct priority: override > AppInfo > DB > config > default', function () {
        $key = 'app_name';
        $default = 'fallback';

        Settings::override([$key => 'OVERRIDE']);

        $value = Settings::get($key, $default);

        expect($value)->toBe('OVERRIDE');

        Settings::clearOverrides();

        $value = Settings::get($key, $default);

        expect($value)->toBe(AppInfo::get('name'));
    });

    it('database wins over config in full chain', function () {
        $key = 'app.locale';
        Setting::factory()->create(['key' => $key, 'value' => 'db_locale']);

        $value = Settings::get($key);

        expect($value)->toBe('db_locale');
    });

    it('default is last resort when nothing else resolves', function () {
        $value = Settings::get('fully.missing.key', 'ultimate_default');

        expect($value)->toBe('ultimate_default');
    });
});
