<?php

declare(strict_types=1);

use App\Settings\Enums\MediaCollection;
use App\Settings\Enums\SettingGroup;
use App\Settings\Enums\SettingType;
use App\Settings\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('setting can be created with fillable attributes', function () {
    $setting = Setting::create([
        'key' => 'test_key',
        'value' => 'test_value',
        'type' => 'string',
        'description' => 'A test setting',
        'group' => SettingGroup::GENERAL->value,
    ]);

    expect($setting->key)->toBe('test_key');
    expect($setting->value)->toBe('test_value');
    expect($setting->type)->toBe('string');
    expect($setting->description)->toBe('A test setting');
    expect($setting->group)->toBe('general');
});

test('setting primary key is key string', function () {
    $setting = new Setting;

    expect($setting->getKeyName())->toBe('key');
    expect($setting->incrementing)->toBeFalse();
    expect($setting->getKeyType())->toBe('string');
});

test('scope group filters by group name', function () {
    Setting::create(['key' => 'a', 'value' => '1', 'type' => 'string', 'group' => 'general']);
    Setting::create(['key' => 'b', 'value' => '2', 'type' => 'string', 'group' => 'mail']);

    $generalSettings = Setting::group('general')->get();

    expect($generalSettings)->toHaveCount(1);
    expect($generalSettings->first()->key)->toBe('a');
});

test('scope byKey filters by exact key', function () {
    Setting::create(['key' => 'target', 'value' => 'found', 'type' => 'string']);
    Setting::create(['key' => 'other', 'value' => 'not', 'type' => 'string']);

    $result = Setting::byKey('target')->first();

    expect($result->value)->toBe('found');
});

test('scope inGroup filters by multiple groups', function () {
    Setting::create(['key' => 'a', 'value' => '1', 'type' => 'string', 'group' => 'general']);
    Setting::create(['key' => 'b', 'value' => '2', 'type' => 'string', 'group' => 'mail']);
    Setting::create(['key' => 'c', 'value' => '3', 'type' => 'string', 'group' => 'system']);

    $result = Setting::inGroup(['general', 'mail'])->get();

    expect($result)->toHaveCount(2);
});

test('scope ofType filters by type', function () {
    Setting::create(['key' => 'a', 'value' => '1', 'type' => 'string']);
    Setting::create(['key' => 'b', 'value' => '42', 'type' => 'integer']);

    $result = Setting::ofType(SettingType::INTEGER)->get();

    expect($result)->toHaveCount(1);
    expect($result->first()->key)->toBe('b');
});

test('scope searchable filters by key or description', function () {
    Setting::create(['key' => 'brand_name', 'value' => 'Acme', 'type' => 'string', 'description' => 'The brand name']);
    Setting::create(['key' => 'site_title', 'value' => 'Test', 'type' => 'string', 'description' => 'The site title']);

    $byKey = Setting::searchable('brand')->get();
    expect($byKey)->toHaveCount(1);
    expect($byKey->first()->key)->toBe('brand_name');

    $byDesc = Setting::searchable('site')->get();
    expect($byDesc)->toHaveCount(1);
    expect($byDesc->first()->key)->toBe('site_title');
});

test('setting type enum has all valid types', function () {
    expect(SettingType::values())->toBe([
        'string', 'integer', 'float', 'boolean', 'json', 'encrypted', 'null',
    ]);
});

test('media collection enum has correct values', function () {
    expect(MediaCollection::LOGO->value)->toBe('brand_logo');
    expect(MediaCollection::FAVICON->value)->toBe('brand_favicon');
});
