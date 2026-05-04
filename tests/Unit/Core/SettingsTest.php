<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use App\Domain\Core\Models\Setting;
use App\Domain\Core\Support\AppInfo;
use App\Domain\Core\Support\Settings;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Settings::clearOverrides();
    Cache::flush();
});

test('get returns override value when set', function () {
    Settings::override(['test.key' => 'override-value']);

    expect(Settings::get('test.key'))->toBe('override-value');
});

test('get returns database value when no override', function () {
    Setting::create([
        'key' => 'test.db_key',
        'value' => 'db-value',
        'type' => 'string',
    ]);

    expect(Settings::get('test.db_key'))->toBe('db-value');
});

test('get returns default when key not found anywhere', function () {
    expect(Settings::get('nonexistent.key', 'fallback'))->toBe('fallback');
});

test('get returns array of values for multiple keys', function () {
    Settings::override(['key1' => 'value1']);
    Setting::create(['key' => 'key2', 'value' => 'value2', 'type' => 'string']);

    $results = Settings::get(['key1', 'key2', 'nonexistent']);

    expect($results)
        ->toHaveKey('key1', 'value1')
        ->toHaveKey('key2', 'value2')
        ->toHaveKey('nonexistent', null);
});

test('get preserves falsy values from database', function () {
    Setting::create(['key' => 'test.zero', 'value' => '0', 'type' => 'integer']);

    expect(Settings::get('test.zero'))->toBe(0);
});

test('has returns true for existing key', function () {
    Settings::override(['existing.key' => 'value']);

    expect(Settings::has('existing.key'))->toBeTrue();
});

test('has returns false for non-existing key', function () {
    expect(Settings::has('nonexistent.key'))->toBeFalse();
});

test('all returns collection of all settings', function () {
    Setting::create(['key' => 'setting_a', 'value' => 'value1', 'type' => 'string']);
    Setting::create(['key' => 'setting_b', 'value' => 'value2', 'type' => 'string']);

    $all = Settings::all();

    expect($all)->toHaveKey('setting_a', 'value1')
        ->and($all)->toHaveKey('setting_b', 'value2');
});

test('group returns settings for specific group', function () {
    Setting::create(['key' => 'mail.host', 'value' => 'smtp.example.com', 'type' => 'string', 'group' => 'mail']);
    Setting::create(['key' => 'mail.port', 'value' => '587', 'type' => 'integer', 'group' => 'mail']);
    Setting::create(['key' => 'app.name', 'value' => 'MyApp', 'type' => 'string', 'group' => 'app']);

    $mailSettings = Settings::group('mail');

    expect($mailSettings)->toHaveCount(2);
});

test('override merges with existing overrides', function () {
    Settings::override(['key1' => 'value1', 'key2' => 'value2']);
    Settings::override(['key2' => 'updated', 'key3' => 'value3']);

    expect(Settings::get('key1'))->toBe('value1')
        ->and(Settings::get('key2'))->toBe('updated')
        ->and(Settings::get('key3'))->toBe('value3');
});

test('clearOverrides removes all overrides', function () {
    Settings::override(['key1' => 'value1']);
    Settings::clearOverrides();

    expect(Settings::get('key1', 'fallback'))->toBe('fallback');
});

test('forget invalidates cache for key and group', function () {
    Setting::create(['key' => 'forgettable.key', 'value' => 'original', 'type' => 'string', 'group' => 'test_group']);

    $first = Settings::get('forgettable.key');

    Settings::forget('forgettable.key', 'test_group');

    Setting::where('key', 'forgettable.key')->update(['value' => 'updated']);

    $second = Settings::get('forgettable.key');

    expect($first)->toBe('original')
        ->and($second)->toBe('updated');
});

test('resolve single respects override priority over database', function () {
    Setting::create(['key' => 'priority.key', 'value' => 'db-value', 'type' => 'string']);
    Settings::override(['priority.key' => 'override-value']);

    expect(Settings::get('priority.key'))->toBe('override-value');
});

test('resolve single returns AppInfo value for mapped keys', function () {
    AppInfo::clearCache();

    $result = Settings::get('app_name');

    expect($result)->toBeString()->not->toBeEmpty();
});

test('settings class is final', function () {
    $reflection = new \ReflectionClass(Settings::class);

    expect($reflection->isFinal())->toBeTrue();
});

test('all with skipCache forgets cache before fetching', function () {
    Setting::create(['key' => 'cache_test', 'value' => 'v1', 'type' => 'string']);
    $first = Settings::all();

    Setting::where('key', 'cache_test')->update(['value' => 'v2']);

    $second = Settings::all(skipCache: true);

    expect($first)->toHaveKey('cache_test', 'v1')
        ->and($second)->toHaveKey('cache_test', 'v2');
});

test('all returns collection when settings exist', function () {
    Setting::create(['key' => 'error_test_a', 'value' => 'v1', 'type' => 'string']);
    Setting::create(['key' => 'error_test_b', 'value' => 'v2', 'type' => 'string']);

    $result = Settings::all();

    expect($result)->toBeInstanceOf(Collection::class)
        ->toHaveKey('error_test_a', 'v1')
        ->toHaveKey('error_test_b', 'v2');
});

test('group with skipCache forgets cache before fetching', function () {
    Setting::create(['key' => 'group_test.a', 'value' => 'v1', 'type' => 'string', 'group' => 'grp']);
    Settings::group('grp');

    Setting::create(['key' => 'group_test.b', 'value' => 'v2', 'type' => 'string', 'group' => 'grp']);

    $result = Settings::group('grp', skipCache: true);

    expect($result)->toHaveCount(2);
});

test('group returns settings for existing group', function () {
    Log::shouldReceive('error')
        ->never();

    Cache::flush();

    Setting::create(['key' => 'group_ok.a', 'value' => 'test', 'type' => 'string', 'group' => 'ok_group']);

    expect(Settings::group('ok_group'))->toHaveCount(1);
});

test('forget works without group parameter', function () {
    Setting::create(['key' => 'forget_no_group', 'value' => 'test', 'type' => 'string']);

    Settings::forget('forget_no_group');

    Cache::flush();

    $result = Settings::get('forget_no_group');

    expect($result)->toBe('test');
});

test('resolveSingle skips cache when skipCache is true', function () {
    Setting::create(['key' => 'skip_cache_test', 'value' => 'initial', 'type' => 'string']);
    $first = Settings::get('skip_cache_test');

    Setting::where('key', 'skip_cache_test')->update(['value' => 'updated']);

    $second = Settings::get('skip_cache_test', default: null, skipCache: true);

    expect($first)->toBe('initial')
        ->and($second)->toBe('updated');
});

test('resolveSingle returns config fallback when no override or database value', function () {
    config(['app.timezone' => 'UTC']);

    expect(Settings::get('app.timezone'))->toBe('UTC');
});

test('resolveSingle returns default when no database value and no config', function () {
    expect(Settings::get('completely_nonexistent', 'my-fallback'))->toBe('my-fallback');
});
