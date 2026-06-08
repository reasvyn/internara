<?php

declare(strict_types=1);

use App\Settings\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;

uses(RefreshDatabase::class);

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
    $all = Settings::all();

    expect($all)->toBeInstanceOf(Collection::class);
});

test('settings has returns boolean', function () {
    expect(Settings::has('non_existent'))->toBeFalse();
});

test('settings keys returns collection', function () {
    $keys = Settings::keys();

    expect($keys)->toBeInstanceOf(Collection::class);
});

test('settings groups returns collection', function () {
    $groups = Settings::groups();

    expect($groups)->toBeInstanceOf(Collection::class);
});

test('settings countByGroup returns collection', function () {
    $counts = Settings::countByGroup();

    expect($counts)->toBeInstanceOf(Collection::class);
});

test('settings hasGroup returns boolean', function () {
    expect(Settings::hasGroup('general'))->toBeFalse();
    expect(Settings::hasGroup('nonexistent'))->toBeFalse();
});

test('settings forget and forgetGroup work without error', function () {
    Settings::forget('some_key', 'general');
    Settings::forgetGroup('general');

    expect(true)->toBeTrue();
});
