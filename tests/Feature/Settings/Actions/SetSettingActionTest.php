<?php

declare(strict_types=1);

namespace Tests\Feature\Settings\Actions;

use App\Settings\Actions\SetSettingAction;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(LazilyRefreshDatabase::class);

test('set setting action creates or updates settings and detects type', function () {
    $action = new SetSettingAction;

    $setting = $action->execute('test_setting_key', 'hello_val', 'general', 'Test setting');

    expect($setting->key)->toBe('test_setting_key');
    expect($setting->value)->toBe('hello_val');
    expect($setting->type)->toBe('string');
    expect($setting->group)->toBe('general');
    expect($setting->description)->toBe('Test setting');

    // Test type detection for integer
    $settingInt = $action->execute('test_int_key', 42);
    expect($settingInt->type)->toBe('integer');

    // Test type detection for boolean
    $settingBool = $action->execute('test_bool_key', true);
    expect($settingBool->type)->toBe('boolean');
});

test('set setting action validates key format', function () {
    $action = new SetSettingAction;

    expect(fn () => $action->execute('INVALID KEY', 'value'))->toThrow(ValidationException::class);
});
