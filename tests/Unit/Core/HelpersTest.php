<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use App\Domain\Core\Support\AppInfo;
use App\Domain\Core\Support\Settings;

test('setting helper returns value for string key', function () {
    Settings::override(['test.helper.key' => 'helper-value']);

    expect(setting('test.helper.key'))->toBe('helper-value');
});

test('setting helper returns default for missing key', function () {
    expect(setting('missing.key', 'default'))->toBe('default');
});

test('setting helper returns Settings instance when key is null', function () {
    $result = setting();

    expect($result)->toBeInstanceOf(Settings::class);
});

test('setting helper returns default for array key', function () {
    expect(setting(['key1', 'key2'], 'default'))->toBe('default');
});

test('is_debug_mode helper delegates to Environment', function () {
    config(['app.debug' => true]);

    expect(is_debug_mode())->toBeTrue();
});

test('is_development helper delegates to Environment', function () {
    app()->detectEnvironment(fn () => 'local');

    expect(is_development())->toBeTrue();
});

test('is_testing helper delegates to Environment', function () {
    $result = is_testing();

    expect($result)->toBeBool();
});

test('is_maintenance helper delegates to Environment', function () {
    expect(is_maintenance())->toBeFalse();
});

test('brand helper delegates to AppMetadata', function () {
    $name = brand('name');

    expect($name)->toBeString()->not->toBeEmpty();
});

test('brand helper returns default for unknown key', function () {
    expect(brand('unknown', 'fallback'))->toBe('fallback');
});

test('app_info helper returns all metadata when key is null', function () {
    AppInfo::clearCache();

    $result = app_info();

    expect($result)->toBeArray()
        ->toHaveKey('name')
        ->toHaveKey('version');
});

test('app_info helper returns specific value', function () {
    AppInfo::clearCache();

    $name = app_info('name');
    $version = app_info('version');

    expect($name)->toBeString()
        ->and($version)->toBeString();
});

test('app_info helper returns default for missing key', function () {
    expect(app_info('nonexistent', 'fallback'))->toBe('fallback');
});

test('helpers are defined as functions', function () {
    expect(function_exists('setting'))->toBeTrue();
    expect(function_exists('is_debug_mode'))->toBeTrue();
    expect(function_exists('is_development'))->toBeTrue();
    expect(function_exists('is_testing'))->toBeTrue();
    expect(function_exists('is_maintenance'))->toBeTrue();
    expect(function_exists('brand'))->toBeTrue();
    expect(function_exists('app_info'))->toBeTrue();
});

test('setting helper with array key returns default value', function () {
    Settings::override(['key1' => 'value1']);

    expect(setting(['key1', 'key2'], 'array-default'))->toBe('array-default');
});

test('setting helper with skipCache parameter', function () {
    Settings::override(['cache.key' => 'cached-value']);

    expect(setting('cache.key', null, true))->toBe('cached-value');
});

test('is_debug_mode returns false when debug is disabled', function () {
    config(['app.debug' => false]);

    expect(is_debug_mode())->toBeFalse();
});

test('brand helper with colors key returns array', function () {
    $colors = brand('colors');

    expect($colors)->toBeArray()
        ->toHaveKey('primary')
        ->toHaveKey('secondary')
        ->toHaveKey('accent');
});

test('app_info helper with null key returns array', function () {
    AppInfo::clearCache();

    $result = app_info(null, 'default');

    expect($result)->toBeArray();
});
