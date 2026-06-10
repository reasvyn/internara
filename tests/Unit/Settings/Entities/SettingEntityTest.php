<?php

declare(strict_types=1);

use App\Settings\Entities\SettingEntity;
use App\Settings\Models\Setting;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('setting entity can be created from model', function () {
    $setting = Setting::create(['key' => 'site.name', 'value' => 'Internara', 'group' => 'general']);

    $entity = SettingEntity::fromModel($setting);

    expect($entity->key())->toBe('site.name');
    expect($entity->value())->toBe('Internara');
    expect($entity->group())->toBe('general');
});

test('setting entity identifies boolean type', function () {
    $entity = new SettingEntity(key: 'feature_x', value: true, type: 'boolean', group: null);

    expect($entity->isBoolean())->toBeTrue();
    expect($entity->booleanValue())->toBeTrue();
});

test('setting entity identifies json type', function () {
    $entity = new SettingEntity(key: 'colors', value: ['primary' => '#000'], type: 'json', group: null);

    expect($entity->isJson())->toBeTrue();
    expect($entity->jsonValue())->toBe(['primary' => '#000']);
});

test('setting entity returns empty array for non-json value', function () {
    $entity = new SettingEntity(key: 'name', value: 'test', type: 'string', group: null);

    expect($entity->isJson())->toBeFalse();
    expect($entity->jsonValue())->toBe([]);
});

test('setting entity identifies encrypted type', function () {
    $entity = new SettingEntity(key: 'secret', value: 'encrypted_value', type: 'encrypted', group: null);

    expect($entity->isEncrypted())->toBeTrue();
});

test('setting entity checks empty values', function () {
    $entity = new SettingEntity(key: 'empty', value: null, type: null, group: null);

    expect($entity->isEmpty())->toBeTrue();
});
