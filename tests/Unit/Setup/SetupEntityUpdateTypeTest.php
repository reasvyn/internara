<?php

declare(strict_types=1);

namespace Tests\Unit\Setup\Entities;

use App\Settings\Data\SettingEntryData;
use App\Setup\Entities\SetupEntity;

test('toSettingsEntries uses known type from map for known keys', function () {
    $entries = SetupEntity::toSettingsEntries(['is_installed' => true]);

    expect($entries)->toHaveCount(1);
    expect($entries[0])->toBeInstanceOf(SettingEntryData::class);
    expect($entries[0]->key)->toBe('setup.is_installed');
    expect($entries[0]->value)->toBeTrue();
    expect($entries[0]->type)->toBe('boolean');
    expect($entries[0]->group)->toBe('setup');
});

test('toSettingsEntries falls back to boolean type for bool values with unknown key', function () {
    $entries = SetupEntity::toSettingsEntries(['some_custom_flag' => false]);

    expect($entries[0]->type)->toBe('boolean');
    expect($entries[0]->key)->toBe('setup.some_custom_flag');
});

test('toSettingsEntries falls back to json type for array values with unknown key', function () {
    $entries = SetupEntity::toSettingsEntries(['custom_list' => ['a', 'b']]);

    expect($entries[0]->type)->toBe('json');
});

test('toSettingsEntries falls back to integer type for int values with unknown key', function () {
    $entries = SetupEntity::toSettingsEntries(['version' => 3]);

    expect($entries[0]->type)->toBe('integer');
});

test('toSettingsEntries falls back to string type for string values with unknown key', function () {
    $entries = SetupEntity::toSettingsEntries(['label' => 'hello']);

    expect($entries[0]->type)->toBe('string');
});
