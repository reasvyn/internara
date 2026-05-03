<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use App\Casts\SettingValueCast;
use App\Domain\Core\Models\Setting;

test('setting value cast returns string value as-is', function () {
    $cast = new SettingValueCast;
    $model = new Setting;

    $result = $cast->get($model, 'value', 'hello', ['type' => 'string']);

    expect($result)->toBe('hello');
});

test('setting value cast casts boolean correctly', function () {
    $cast = new SettingValueCast;
    $model = new Setting;

    expect($cast->get($model, 'value', '1', ['type' => 'boolean']))->toBeTrue();
    expect($cast->get($model, 'value', '0', ['type' => 'boolean']))->toBeFalse();
});

test('setting value cast casts integer correctly', function () {
    $cast = new SettingValueCast;
    $model = new Setting;

    $result = $cast->get($model, 'value', '42', ['type' => 'integer']);

    expect($result)->toBe(42)->toBeInt();
});

test('setting value cast casts float correctly', function () {
    $cast = new SettingValueCast;
    $model = new Setting;

    $result = $cast->get($model, 'value', '3.14', ['type' => 'float']);

    expect($result)->toBe(3.14)->toBeFloat();
});

test('setting value cast decodes json type to array', function () {
    $cast = new SettingValueCast;
    $model = new Setting;

    $result = $cast->get($model, 'value', '{"key":"value"}', ['type' => 'json']);

    expect($result)->toBe(['key' => 'value']);
});

test('setting value cast returns null for null type', function () {
    $cast = new SettingValueCast;
    $model = new Setting;

    $result = $cast->get($model, 'value', 'anything', ['type' => 'null']);

    expect($result)->toBeNull();
});

test('setting value cast returns null for null database value', function () {
    $cast = new SettingValueCast;
    $model = new Setting;

    $result = $cast->get($model, 'value', null, ['type' => 'string']);

    expect($result)->toBeNull();
});

test('setting value cast set detects boolean type', function () {
    $cast = new SettingValueCast;
    $model = new Setting;

    $result = $cast->set($model, 'value', true, []);

    expect($result['value'])->toBe(1);
    expect($result['type'])->toBe('boolean');
});

test('setting value cast set detects integer type', function () {
    $cast = new SettingValueCast;
    $model = new Setting;

    $result = $cast->set($model, 'value', 42, []);

    expect($result['value'])->toBe('42');
    expect($result['type'])->toBe('integer');
});

test('setting value cast set detects float type', function () {
    $cast = new SettingValueCast;
    $model = new Setting;

    $result = $cast->set($model, 'value', 3.14, []);

    expect($result['value'])->toBe('3.14');
    expect($result['type'])->toBe('float');
});

test('setting value cast set detects json type for arrays', function () {
    $cast = new SettingValueCast;
    $model = new Setting;

    $result = $cast->set($model, 'value', ['foo' => 'bar'], []);

    expect($result['value'])->toBe('{"foo":"bar"}');
    expect($result['type'])->toBe('json');
});

test('setting value cast set detects null type', function () {
    $cast = new SettingValueCast;
    $model = new Setting;

    $result = $cast->set($model, 'value', null, []);

    expect($result['value'])->toBeNull();
    expect($result['type'])->toBe('null');
});

test('setting value cast set defaults to string type', function () {
    $cast = new SettingValueCast;
    $model = new Setting;

    $result = $cast->set($model, 'value', 'hello', []);

    expect($result['value'])->toBe('hello');
    expect($result['type'])->toBe('string');
});
