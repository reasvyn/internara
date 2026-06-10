<?php

declare(strict_types=1);

use App\Settings\Data\SettingData;

test('setting data can be created with mininal fields', function () {
    $data = new SettingData(key: 'site.name');

    expect($data->key)->toBe('site.name');
    expect($data->value)->toBeNull();
    expect($data->type)->toBeNull();
});

test('setting data can be created with all fields', function () {
    $data = new SettingData(key: 'site.name', value: 'Internara', type: 'string', group: 'general');

    expect($data->key)->toBe('site.name');
    expect($data->value)->toBe('Internara');
    expect($data->type)->toBe('string');
    expect($data->group)->toBe('general');
});

test('setting data is immutable', function () {
    $data = new SettingData(key: 'site.name');

    $reflection = new ReflectionClass($data);
    $properties = $reflection->getProperties();

    foreach ($properties as $prop) {
        expect($prop->isReadOnly())->toBeTrue();
    }
});

test('setting data from array', function () {
    $data = SettingData::from(['key' => 'test.key', 'value' => 'test_value', 'type' => 'string']);

    expect($data->key)->toBe('test.key');
    expect($data->value)->toBe('test_value');
});

test('setting data to array', function () {
    $data = new SettingData(key: 'test.key', value: 'test_value', type: 'string', group: 'test');

    expect($data->toArray())->toBe([
        'key' => 'test.key',
        'value' => 'test_value',
        'type' => 'string',
        'group' => 'test',
        'description' => null,
    ]);
});
