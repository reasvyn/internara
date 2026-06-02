<?php

declare(strict_types=1);

use App\Domain\Core\Support\CacheKeys;
use App\Domain\Settings\Models\Setting;
use App\Domain\Settings\Support\Settings;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

uses(LazilyRefreshDatabase::class);

describe('Settings', function () {
    beforeEach(function () {
        Settings::clearOverrides();
        Cache::flush();
    });

    describe('get', function () {
        it('returns setting from database', function () {
            Setting::create([
                'key' => 'test_key',
                'value' => 'test_value',
            ]);

            expect(Settings::get('test_key'))->toBe('test_value');
        });

        it('returns default when key not found', function () {
            expect(Settings::get('nonexistent', 'fallback'))->toBe('fallback');
        });

        it('returns null default when key not found and no default given', function () {
            expect(Settings::get('nonexistent'))->toBeNull();
        });

        it('resolves multiple keys as array', function () {
            Setting::create(['key' => 'first', 'value' => 'one']);
            Setting::create(['key' => 'second', 'value' => 'two']);

            $results = Settings::get(['first', 'second', 'missing']);

            expect($results['first'])->toBe('one');
            expect($results['second'])->toBe('two');
            expect($results['missing'])->toBeNull();
        });

        it('respects runtime overrides', function () {
            Settings::override(['test_override' => 'overridden']);

            expect(Settings::get('test_override'))->toBe('overridden');
        });

        it('resolves AppInfo keys as SSoT', function () {
            $name = Settings::get('app_name');

            expect($name)->toBe('Internara');
        });

        it('falls back to config value', function () {
            config(['app.test_config' => 'config_value']);

            expect(Settings::get('app.test_config'))->toBe('config_value');
        });

        it('returns first non-null value in resolution chain', function () {
            Settings::override(['existing_key' => 'override_value']);
            Setting::create(['key' => 'existing_key', 'value' => 'db_value']);

            expect(Settings::get('existing_key'))->toBe('override_value');
        });
    });

    describe('has', function () {
        it('returns true when setting has value', function () {
            Settings::override(['active' => true]);

            expect(Settings::has('active'))->toBeTrue();
        });

        it('returns false when setting is null', function () {
            expect(Settings::has('nonexistent'))->toBeFalse();
        });
    });

    describe('set', function () {
        it('creates a new setting', function () {
            $updated = Settings::set(['new_key' => 'new_value']);

            expect($updated)->toBe(1);
            expect(Settings::get('new_key'))->toBe('new_value');
        });

        it('updates an existing setting', function () {
            Setting::create(['key' => 'updatable', 'value' => 'old']);

            $updated = Settings::set(['updatable' => 'new']);

            expect($updated)->toBe(1);
            expect(Settings::get('updatable'))->toBe('new');
        });

        it('skips update when value unchanged', function () {
            Setting::create(['key' => 'stable', 'value' => 'same']);

            $updated = Settings::set(['stable' => 'same']);

            expect($updated)->toBe(0);
        });

        it('accepts array with extra attributes', function () {
            Settings::set(['complex' => [
                'value' => 'val',
                'group' => 'testing',
                'description' => 'A test setting',
            ]]);

            $setting = Setting::where('key', 'complex')->first();

            expect($setting->value)->toBe('val');
            expect($setting->group)->toBe('testing');
            expect($setting->description)->toBe('A test setting');
        });

        it('validates key format', function () {
            expect(fn () => Settings::set(['InvalidKey' => 'x']))->toThrow(ValidationException::class);
        });
    });

    describe('all', function () {
        it('returns all settings as collection', function () {
            Setting::create(['key' => 'a', 'value' => '1']);
            Setting::create(['key' => 'b', 'value' => '2']);

            $all = Settings::all();

            expect($all)->toHaveCount(2);
            expect($all['a'])->toBe('1');
            expect($all['b'])->toBe('2');
        });

        it('handles empty database', function () {
            $all = Settings::all();

            expect($all)->toBeInstanceOf(Collection::class);
            expect($all)->toBeEmpty();
        });
    });

    describe('group', function () {
        it('returns settings filtered by group', function () {
            Setting::create(['key' => 'mail_host', 'value' => 'smtp.mailtrap.io', 'group' => 'mail']);
            Setting::create(['key' => 'mail_port', 'value' => '587', 'group' => 'mail']);
            Setting::create(['key' => 'app_name', 'value' => 'Test', 'group' => 'general']);

            $group = Settings::group('mail');

            expect($group)->toHaveCount(2);
            expect($group->pluck('key')->toArray())->toEqual(['mail_host', 'mail_port']);
        });

        it('caches group results', function () {
            Setting::create(['key' => 'test_grp', 'value' => 'x', 'group' => 'grp']);

            Settings::group('grp');

            expect(Cache::has(CacheKeys::SETTINGS_GROUP.'grp'))->toBeTrue();
        });
    });

    describe('keys', function () {
        it('returns all setting keys', function () {
            Setting::create(['key' => 'alpha', 'value' => '1']);
            Setting::create(['key' => 'beta', 'value' => '2']);

            $keys = Settings::keys();

            expect($keys)->toContain('alpha');
            expect($keys)->toContain('beta');
        });
    });

    describe('override/clearOverrides', function () {
        it('clearOverrides removes all overrides', function () {
            Settings::override(['temp' => 'value']);
            expect(Settings::get('temp'))->toBe('value');

            Settings::clearOverrides();
            expect(Settings::get('temp'))->toBeNull();
        });
    });

    describe('forget', function () {
        it('forgets specific key cache', function () {
            Setting::create(['key' => 'cache_me', 'value' => 'v']);

            Settings::get('cache_me');
            expect(Cache::has(CacheKeys::SETTINGS_KEY.'cache_me'))->toBeTrue();

            Settings::forget('cache_me');
            expect(Cache::has(CacheKeys::SETTINGS_KEY.'cache_me'))->toBeFalse();
        });

        it('forgets group cache when group is provided', function () {
            Setting::create(['key' => 'grp_key', 'value' => 'v', 'group' => 'testg']);
            Settings::group('testg');
            expect(Cache::has(CacheKeys::SETTINGS_GROUP.'testg'))->toBeTrue();

            Settings::forget('grp_key', 'testg');
            expect(Cache::has(CacheKeys::SETTINGS_GROUP.'testg'))->toBeFalse();
        });

        it('forgets THEME_CSS_VARIABLES when key contains color', function () {
            Cache::put(CacheKeys::THEME_CSS_VARIABLES, ['light' => []], 3600);

            Settings::forget('primary_color');

            expect(Cache::has(CacheKeys::THEME_CSS_VARIABLES))->toBeFalse();
        });
    });

    describe('countByGroup', function () {
        it('counts settings per group', function () {
            Setting::create(['key' => 'a', 'value' => '1', 'group' => 'mail']);
            Setting::create(['key' => 'b', 'value' => '2', 'group' => 'mail']);
            Setting::create(['key' => 'c', 'value' => '3', 'group' => 'general']);

            $counts = Settings::countByGroup();

            expect($counts['mail'])->toBe(2);
            expect($counts['general'])->toBe(1);
        });
    });

    describe('groups', function () {
        it('returns distinct group names', function () {
            Setting::create(['key' => 'a', 'value' => '1', 'group' => 'mail']);
            Setting::create(['key' => 'b', 'value' => '2', 'group' => 'branding']);

            $groups = Settings::groups();

            expect($groups)->toContain('mail');
            expect($groups)->toContain('branding');
        });
    });

    describe('hasGroup', function () {
        it('returns true if group has settings', function () {
            Setting::create(['key' => 'x', 'value' => '1', 'group' => 'existing']);

            expect(Settings::hasGroup('existing'))->toBeTrue();
        });

        it('returns false if group is empty', function () {
            expect(Settings::hasGroup('empty_group'))->toBeFalse();
        });
    });
});
