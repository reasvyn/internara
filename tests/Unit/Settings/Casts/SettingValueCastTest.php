<?php

declare(strict_types=1);

use App\Settings\Casts\SettingValueCast;
use App\Settings\Models\Setting;

test('get returns null for null value', function () {
    $cast = new SettingValueCast;
    $model = new Setting;

    expect($cast->get($model, 'value', null, []))->toBeNull();
});

test('get casts string type', function () {
    $cast = new SettingValueCast;
    $model = new Setting;

    $result = $cast->get($model, 'value', 'hello', ['type' => 'string']);

    expect($result)->toBe('hello');
});

test('get casts integer type', function () {
    $cast = new SettingValueCast;
    $model = new Setting;

    $result = $cast->get($model, 'value', '42', ['type' => 'integer']);

    expect($result)->toBe(42)->toBeInt();
});

test('get casts float type', function () {
    $cast = new SettingValueCast;
    $model = new Setting;

    $result = $cast->get($model, 'value', '3.14', ['type' => 'float']);

    expect($result)->toBe(3.14)->toBeFloat();
});

test('get casts boolean type', function () {
    $cast = new SettingValueCast;
    $model = new Setting;

    expect($cast->get($model, 'value', '1', ['type' => 'boolean']))->toBeTrue();
    expect($cast->get($model, 'value', '0', ['type' => 'boolean']))->toBeFalse();
});

test('get casts json type', function () {
    $cast = new SettingValueCast;
    $model = new Setting;

    $result = $cast->get($model, 'value', '{"key":"val"}', ['type' => 'json']);

    expect($result)->toBe(['key' => 'val']);
});

test('get returns empty array for invalid json', function () {
    $cast = new SettingValueCast;
    $model = new Setting;

    $result = $cast->get($model, 'value', 'invalid', ['type' => 'json']);

    expect($result)->toBe([]);
});

test('get returns null for null type', function () {
    $cast = new SettingValueCast;
    $model = new Setting;

    $result = $cast->get($model, 'value', 'anything', ['type' => 'null']);

    expect($result)->toBeNull();
});

test('set encrypts value for encrypted type', function () {
    $cast = new SettingValueCast;
    $model = new Setting;

    $result = $cast->set($model, 'value', 'secret', ['type' => 'encrypted']);

    expect($result['type'])->toBe('encrypted');
    expect($result['value'])->not->toBe('secret');
});

test('set converts null to null type', function () {
    $cast = new SettingValueCast;
    $model = new Setting;

    $result = $cast->set($model, 'value', null, ['type' => 'encrypted']);

    expect($result['type'])->toBe('null');
    expect($result['value'])->toBeNull();
});

test('set auto-detects php types', function () {
    $cast = new SettingValueCast;
    $model = new Setting;

    $intResult = $cast->set($model, 'value', 42, []);
    expect($intResult['type'])->toBe('integer');
    expect($intResult['value'])->toBe('42');

    $boolResult = $cast->set($model, 'value', true, []);
    expect($boolResult['type'])->toBe('boolean');
    expect($boolResult['value'])->toBe(1);

    $arrayResult = $cast->set($model, 'value', ['a' => 1], []);
    expect($arrayResult['type'])->toBe('json');

    $nullResult = $cast->set($model, 'value', null, []);
    expect($nullResult['type'])->toBe('null');
    expect($nullResult['value'])->toBeNull();

    $floatResult = $cast->set($model, 'value', 2.5, []);
    expect($floatResult['type'])->toBe('float');
    expect($floatResult['value'])->toBe('2.5');
});
