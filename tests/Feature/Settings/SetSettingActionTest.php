<?php

declare(strict_types=1);

namespace Tests\Feature\Settings;

use App\Actions\Setting\SetSettingAction;
use App\Models\Setting;
use App\Support\Settings;
use Illuminate\Support\Facades\Cache;

test('set setting action creates a new setting', function () {
    $action = app(SetSettingAction::class);

    $result = $action->execute('new_key', 'new_value', 'general', 'A new setting');

    expect($result)->toBeInstanceOf(Setting::class);
    expect($result->key)->toBe('new_key');
    expect($result->value)->toBe('new_value');
    expect($result->group)->toBe('general');
    expect($result->description)->toBe('A new setting');
});

test('set setting action updates an existing setting', function () {
    Setting::create([
        'key' => 'existing_key',
        'value' => 'old_value',
        'type' => 'string',
    ]);

    $action = app(SetSettingAction::class);
    $result = $action->execute('existing_key', 'updated_value');

    expect($result->value)->toBe('updated_value');
    expect(Setting::where('key', 'existing_key')->count())->toBe(1);
});

test('set setting action stores boolean values with correct type', function () {
    $action = app(SetSettingAction::class);

    $action->execute('bool_setting', true);

    $setting = Setting::where('key', 'bool_setting')->first();
    expect($setting->type)->toBe('boolean');
    expect($setting->value)->toBeTrue();
});

test('set setting action stores integer values with correct type', function () {
    $action = app(SetSettingAction::class);

    $action->execute('int_setting', 42);

    $setting = Setting::where('key', 'int_setting')->first();
    expect($setting->type)->toBe('integer');
    expect($setting->value)->toBe(42);
});

test('set setting action stores json values with correct type', function () {
    $action = app(SetSettingAction::class);

    $action->execute('json_setting', ['foo' => 'bar']);

    $setting = Setting::where('key', 'json_setting')->first();
    expect($setting->type)->toBe('json');
    expect($setting->value)->toBe(['foo' => 'bar']);
});

test('set setting action invalidates cache', function () {
    Settings::clearOverrides();
    Cache::flush();

    $action = app(SetSettingAction::class);
    $action->execute('cache_invalidate_test', 'first');

    expect(Settings::get('cache_invalidate_test'))->toBe('first');

    $action->execute('cache_invalidate_test', 'second');

    expect(Settings::get('cache_invalidate_test'))->toBe('second');
});

test('set setting action batch sets multiple settings', function () {
    $action = app(SetSettingAction::class);

    $results = $action->executeBatch([
        'batch_one' => 'value_one',
        'batch_two' => ['value' => 'value_two', 'group' => 'custom'],
        'batch_three' => 123,
    ]);

    expect($results)->toHaveCount(3);
    expect(Settings::get('batch_one'))->toBe('value_one');
    expect(Settings::get('batch_two'))->toBe('value_two');
    expect(Settings::get('batch_three'))->toBe(123);
});
