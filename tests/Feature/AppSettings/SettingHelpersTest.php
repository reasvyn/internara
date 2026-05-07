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

describe('setting() helper', function () {
    it('returns setting value for a key', function () {
        Setting::factory()->create(['key' => 'helper_key', 'value' => 'helper_val']);

        $value = setting('helper_key');

        expect($value)->toBe('helper_val');
    });

    it('returns default when key is missing', function () {
        expect(setting('missing_key', 'fallback'))->toBe('fallback');
    });

    it('returns Settings instance when key is null', function () {
        $instance = setting();

        expect($instance)->toBeInstanceOf(Settings::class);
    });

    it('bypasses cache when skipCache is true', function () {
        Setting::factory()->create(['key' => 'live', 'value' => 'initial']);

        setting('live');
        Setting::byKey('live')->update(['value' => 'changed']);

        expect(setting('live', skipCache: true))->toBe('changed');
    });

    it('returns default when called with array key', function () {
        expect(setting(['some_key']))->toBeNull();
    });
});

describe('brand() helper', function () {
    it('returns brand name', function () {
        $name = brand('name');

        expect($name)->toBeString();
    });

    it('returns default for unknown key', function () {
        expect(brand('ghost', 'default'))->toBe('default');
    });

    it('returns colors array', function () {
        $colors = brand('colors');

        expect($colors)->toBeArray();
        expect($colors)->toHaveKeys(['primary', 'secondary', 'accent']);
    });
});

describe('app_info() helper', function () {
    it('returns app metadata array when key is null', function () {
        $info = app_info();

        expect($info)->toBeArray();
        expect($info)->toHaveKey('name');
    });

    it('returns specific metadata value', function () {
        $name = app_info('name');

        expect($name)->toBe(AppInfo::get('name'));
    });

    it('returns default when key is missing', function () {
        expect(app_info('ghost_key', 'default'))->toBe('default');
    });
});
