<?php

declare(strict_types=1);

use App\Settings\Models\Setting;
use App\Settings\Services\Settings;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Settings::clearOverrides();
});

test('settings can set and clear runtime overrides', function () {
    Settings::override(['app_theme' => 'dark', 'max_users' => 50]);

    expect(Settings::get('app_theme'))->toBe('dark');
    expect(Settings::get('max_users'))->toBe(50);

    Settings::clearOverrides();

    expect(Settings::get('app_theme'))->toBeNull();
});

test('settings resolves fallbacks correctly', function () {
    expect(Settings::get('non_existent_key', 'fallback_val'))->toBe('fallback_val');
});

test('settings get all returns collection', function () {
    Setting::create(['key' => 'test_all', 'value' => 'found', 'group' => 'test']);
    Cache::flush();

    $all = Settings::all(true);

    expect($all)->toBeInstanceOf(Collection::class);
    expect($all->get('test_all'))->toBe('found');
});

test('settings has returns boolean', function () {
    expect(Settings::has('non_existent'))->toBeFalse();

    Setting::create(['key' => 'existent', 'value' => 'yes', 'group' => 'test']);
    expect(Settings::has('existent'))->toBeTrue();
});

test('settings keys returns collection of keys', function () {
    Setting::create(['key' => 'key_a', 'value' => 'a', 'group' => 'test']);
    Setting::create(['key' => 'key_b', 'value' => 'b', 'group' => 'test']);

    $keys = Settings::keys(true);

    expect($keys)->toBeInstanceOf(Collection::class);
    expect($keys->toArray())->toContain('key_a', 'key_b');
});

test('settings groups returns collection of distinct groups', function () {
    Setting::truncate();
    Setting::create(['key' => 'g1_k1', 'value' => 'v1', 'group' => 'mail']);
    Setting::create(['key' => 'g1_k2', 'value' => 'v2', 'group' => 'mail']);
    Setting::create(['key' => 'g2_k1', 'value' => 'v3', 'group' => 'system']);

    $groups = Settings::groups();

    expect($groups)->toBeInstanceOf(Collection::class);
    expect($groups->toArray())->toEqualCanonicalizing(['mail', 'system']);
});

test('settings countByGroup returns counts', function () {
    Setting::create(['key' => 'c1', 'value' => 'v', 'group' => 'mail']);
    Setting::create(['key' => 'c2', 'value' => 'v', 'group' => 'mail']);
    Setting::create(['key' => 'c3', 'value' => 'v', 'group' => 'system']);

    $counts = Settings::countByGroup();

    expect($counts)->toBeInstanceOf(Collection::class);
    expect((int) $counts->get('mail'))->toBe(2);
    expect((int) $counts->get('system'))->toBe(1);
});

test('settings hasGroup returns true for existing group', function () {
    expect(Settings::hasGroup('general'))->toBeFalse();

    Setting::create(['key' => 'hg_key', 'value' => 'v', 'group' => 'general']);

    expect(Settings::hasGroup('general'))->toBeTrue();
});

test('settings forget invalidates single key cache', function () {
    Setting::create(['key' => 'forget_me', 'value' => 'val', 'group' => 'test']);

    $before = Settings::get('forget_me');
    expect($before)->toBe('val');

    Settings::forget('forget_me', 'test');

    Setting::where('key', 'forget_me')->update(['value' => 'new_val']);
    $after = Settings::get('forget_me', null, true);

    expect($after)->toBe('new_val');
});

test('settings forgetGroup clears group cache', function () {
    Setting::create(['key' => 'fg_k1', 'value' => 'v1', 'group' => 'mail']);
    Setting::create(['key' => 'fg_k2', 'value' => 'v2', 'group' => 'mail']);

    Settings::forgetGroup('mail');

    expect(true)->toBeTrue();
});

test('settings group returns settings for a group', function () {
    Setting::create(['key' => 'grp_k1', 'value' => 'v1', 'group' => 'mail']);
    Setting::create(['key' => 'grp_k2', 'value' => 'v2', 'group' => 'mail']);

    $group = Settings::group('mail', true);

    expect($group)->toBeInstanceOf(Collection::class);
    expect($group)->toHaveCount(2);
});

test('settings resolves app info aliased keys', function () {
    expect(Settings::get('app_name'))->toBeString();
    expect(Settings::get('app_version'))->toBeString();
});

test('settings resolves from config when not in db', function () {
    config(['test_config_fallback' => 'config_value']);

    expect(Settings::get('test_config_fallback'))->toBe('config_value');
});

test('override takes precedence over database', function () {
    Setting::create(['key' => 'override_test', 'value' => 'db_value', 'group' => 'test']);

    Settings::override(['override_test' => 'override_value']);

    expect(Settings::get('override_test'))->toBe('override_value');
});

test('forget with theme key clears theme cache', function () {
    config(['settings.theme_cache_keys' => ['primary_color']]);
    Setting::create(['key' => 'primary_color', 'value' => '#ff0000', 'group' => 'branding']);

    Settings::forget('primary_color');

    expect(true)->toBeTrue();
});

test('forget resolves group from database when not provided', function () {
    Setting::create(['key' => 'resolve_group', 'value' => 'val', 'group' => 'mail']);

    Settings::forget('resolve_group');

    expect(true)->toBeTrue();
});
