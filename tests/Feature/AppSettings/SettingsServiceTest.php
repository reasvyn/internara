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

describe('get', function () {
    it('returns value from database', function () {
        Setting::factory()->create(['key' => 'site_name', 'value' => 'Internara']);

        $value = Settings::get('site_name');

        expect($value)->toBe('Internara');
    });

    it('returns default when key does not exist', function () {
        $value = Settings::get('nonexistent', 'fallback');

        expect($value)->toBe('fallback');
    });

    it('returns multiple keys as associative array', function () {
        Setting::factory()->create(['key' => 'key_a', 'value' => 'a']);
        Setting::factory()->create(['key' => 'key_b', 'value' => 'b']);

        $values = Settings::get(['key_a', 'key_b']);

        expect($values)->toBe(['key_a' => 'a', 'key_b' => 'b']);
    });

    it('bypasses cache when skipCache is true', function () {
        Setting::factory()->create(['key' => 'dynamic', 'value' => 'original']);

        Settings::get('dynamic');
        Setting::byKey('dynamic')->update(['value' => 'updated']);

        $cached = Settings::get('dynamic');
        expect($cached)->toBe('original');

        $fresh = Settings::get('dynamic', null, skipCache: true);
        expect($fresh)->toBe('updated');
    });
});

describe('resolution chain', function () {
    it('resolves runtime overrides first', function () {
        Setting::factory()->create(['key' => 'theme', 'value' => 'light']);

        Settings::override(['theme' => 'dark']);

        expect(Settings::get('theme'))->toBe('dark');
    });

    it('resolves AppInfo SSoT for mapped keys', function () {
        $value = Settings::get('app_name');

        expect($value)->toBe(AppInfo::get('name'));
    });

    it('resolves Laravel config fallback', function () {
        $value = Settings::get('app.debug');

        expect($value)->toBe(config('app.debug'));
    });

    it('does not prefer config over database', function () {
        Setting::factory()->create(['key' => 'app.debug', 'value' => 'should_win']);

        $value = Settings::get('app.debug');

        expect($value)->toBe('should_win');
    });
});

describe('set', function () {
    it('creates a new setting', function () {
        $count = Settings::set(['new_key' => 'new_value']);

        expect($count)->toBe(1);
        expect(Setting::byKey('new_key')->exists())->toBeTrue();
    });

    it('updates an existing setting', function () {
        Setting::factory()->create(['key' => 'existing', 'value' => 'old']);

        $count = Settings::set(['existing' => 'updated']);

        expect($count)->toBe(1);
        expect(Setting::byKey('existing')->first()->value)->toBe('updated');
    });

    it('handles array with metadata', function () {
        Settings::set([
            'meta_key' => ['value' => 'val', 'group' => 'meta', 'description' => 'Meta setting'],
        ]);

        $setting = Setting::byKey('meta_key')->first();
        expect($setting->value)->toBe('val');
        expect($setting->group)->toBe('meta');
    });

    it('invalidates cache after setting', function () {
        Setting::factory()->create(['key' => 'cached_key', 'value' => 'before']);

        Settings::get('cached_key');
        Settings::set(['cached_key' => 'after']);

        expect(Settings::get('cached_key'))->toBe('after');
    });
});

describe('has', function () {
    it('returns true when setting exists with value', function () {
        Setting::factory()->create(['key' => 'exists', 'value' => 'something']);

        expect(Settings::has('exists'))->toBeTrue();
    });

    it('returns false when setting does not exist', function () {
        expect(Settings::has('imaginary'))->toBeFalse();
    });
});

describe('all', function () {
    it('returns all settings as key-value collection', function () {
        Setting::factory()->create(['key' => 'k1', 'value' => 'v1']);
        Setting::factory()->create(['key' => 'k2', 'value' => 'v2']);

        $all = Settings::all();

        expect($all)->toHaveCount(2);
        expect($all['k1'])->toBe('v1');
        expect($all['k2'])->toBe('v2');
    });
});

describe('groups', function () {
    beforeEach(function () {
        Setting::factory()->create(['group' => 'mail', 'key' => 'm1']);
        Setting::factory()->create(['group' => 'mail', 'key' => 'm2']);
        Setting::factory()->create(['group' => 'general', 'key' => 'g1']);
    });

    it('can get settings by group', function () {
        $mailSettings = Settings::group('mail');

        expect($mailSettings)->toHaveCount(2);
    });

    it('can list all groups', function () {
        $groups = Settings::groups();

        expect($groups)->toContain('mail', 'general');
    });

    it('can check if a group exists', function () {
        expect(Settings::hasGroup('mail'))->toBeTrue();
        expect(Settings::hasGroup('nonexistent'))->toBeFalse();
    });

    it('can count by group', function () {
        $counts = Settings::countByGroup();

        expect($counts['mail'])->toBe(2);
        expect($counts['general'])->toBe(1);
    });
});

describe('keys', function () {
    it('returns all setting keys in order', function () {
        Setting::factory()->create(['key' => 'z_last', 'value' => 'z']);
        Setting::factory()->create(['key' => 'a_first', 'value' => 'a']);

        $keys = Settings::keys();

        expect($keys->first())->toBe('a_first');
        expect($keys->last())->toBe('z_last');
    });
});

describe('forget', function () {
    it('invalidates cache for a specific key', function () {
        Setting::factory()->create(['key' => 'cached_val', 'value' => 'original']);

        Settings::get('cached_val');
        Setting::byKey('cached_val')->update(['value' => 'updated']);

        Settings::forget('cached_val');

        expect(Settings::get('cached_val'))->toBe('updated');
    });
});

describe('override', function () {
    it('allows runtime overrides that take priority', function () {
        Settings::override(['temporary' => 'override_value']);

        expect(Settings::get('temporary'))->toBe('override_value');
    });

    it('can clear all overrides', function () {
        Settings::override(['tmp' => 'val']);
        Settings::clearOverrides();

        expect(Settings::get('tmp'))->toBeNull();
    });
});
