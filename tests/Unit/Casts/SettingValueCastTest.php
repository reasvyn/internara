<?php

declare(strict_types=1);

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can cast string value', function () {
    $setting = Setting::factory()->string()->create(['value' => 'test string']);
    expect($setting->value)->toBe('test string');
});

it('can cast integer value', function () {
    $setting = Setting::factory()->integer()->create(['value' => 42]);
    expect($setting->value)->toBe(42);
});

it('can cast float value', function () {
    $setting = Setting::factory()->float()->create(['value' => 3.14]);
    expect($setting->value)->toBe(3.14);
});

it('can cast boolean value', function () {
    $setting = Setting::factory()->boolean()->create(['value' => true]);
    expect($setting->value)->toBeTrue();

    $setting = Setting::factory()->boolean()->create(['value' => false]);
    expect($setting->value)->toBeFalse();
});

it('can cast json/array value', function () {
    $data = ['key' => 'value', 'nested' => ['a' => 1]];
    $setting = Setting::factory()->json()->create(['value' => $data]);
    expect($setting->value)->toBe($data);
});

it('can cast null value', function () {
    $setting = Setting::factory()->nullType()->create(['value' => null]);
    expect($setting->value)->toBeNull();
});

it('can set and get string value', function () {
    $setting = Setting::create([
        'key' => 'test_string',
        'value' => 'hello world',
        'type' => 'string',
    ]);
    expect($setting->fresh()->value)->toBe('hello world');
});

it('can set and get integer value', function () {
    $setting = Setting::create([
        'key' => 'test_integer',
        'value' => 100,
        'type' => 'integer',
    ]);
    expect($setting->fresh()->value)->toBe(100);
});

it('can set and get float value', function () {
    $setting = Setting::create([
        'key' => 'test_float',
        'value' => 99.99,
        'type' => 'float',
    ]);
    expect($setting->fresh()->value)->toBe(99.99);
});

it('can set and get boolean value', function () {
    $setting = Setting::create([
        'key' => 'test_bool',
        'value' => true,
        'type' => 'boolean',
    ]);
    expect($setting->fresh()->value)->toBeTrue();
});

it('can set and get array value as json', function () {
    $array = ['foo' => 'bar', 'items' => [1, 2, 3]];
    $setting = Setting::create([
        'key' => 'test_array',
        'value' => $array,
        'type' => 'json',
    ]);
    expect($setting->fresh()->value)->toBe($array);
});

it('can set and get null value', function () {
    $setting = Setting::create([
        'key' => 'test_null',
        'value' => null,
        'type' => 'null',
    ]);
    expect($setting->fresh()->value)->toBeNull();
});

it('casts empty value to null', function () {
    $setting = new Setting([
        'key' => 'test_empty',
        'value' => null,
        'type' => 'string',
    ]);
    expect($setting->value)->toBeNull();
});

it('handles invalid json gracefully', function () {
    $setting = Setting::create([
        'key' => 'test_invalid_json',
        'value' => 'invalid json',
        'type' => 'json',
    ]);
    expect($setting->fresh()->value)->toBe([]);
});
