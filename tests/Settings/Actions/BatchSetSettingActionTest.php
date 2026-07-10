<?php

declare(strict_types=1);

use App\Settings\Actions\BatchSetSettingAction;
use App\Settings\Actions\SetSettingAction;
use App\Settings\Data\SettingEntryData;
use App\Settings\Models\Setting;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('batch set creates multiple settings', function () {
    $action = new BatchSetSettingAction(new SetSettingAction);

    $results = $action->execute(
        new SettingEntryData(key: 'key_one', value: 'value_one'),
        new SettingEntryData(key: 'key_two', value: 42),
        new SettingEntryData(key: 'key_three', value: true),
    );

    expect($results)->toHaveCount(3);
    expect(Setting::where('key', 'key_one')->exists())->toBeTrue();
    expect(Setting::where('key', 'key_two')->exists())->toBeTrue();
    expect(Setting::where('key', 'key_three')->exists())->toBeTrue();
});

test('batch set with array config applies type and group', function () {
    $action = new BatchSetSettingAction(new SetSettingAction);

    $results = $action->execute(
        new SettingEntryData(key: 'encrypted_key', value: 'secret', type: 'encrypted', group: 'system', description: 'A secret'),
    );
    $setting = $results->first();

    expect($setting->type)->toBe('encrypted');
    expect($setting->group)->toBe('system');
    expect($setting->description)->toBe('A secret');
});

test('batch set is transactional', function () {
    $action = new BatchSetSettingAction(new SetSettingAction);

    $results = $action->execute(
        new SettingEntryData(key: 'key_a', value: 'val_a'),
        new SettingEntryData(key: 'key_b', value: 'val_b'),
    );

    expect($results)->toHaveCount(2);
});
