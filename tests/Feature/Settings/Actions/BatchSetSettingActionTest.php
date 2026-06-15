<?php

declare(strict_types=1);

use App\Settings\Actions\BatchSetSettingAction;
use App\Settings\Actions\SetSettingAction;
use App\Settings\Models\Setting;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('batch set creates multiple settings', function () {
    $action = new BatchSetSettingAction(new SetSettingAction);
    $settings = [
        'key_one' => 'value_one',
        'key_two' => 42,
        'key_three' => true,
    ];

    $results = $action->execute($settings);

    expect($results)->toHaveCount(3);
    expect(Setting::where('key', 'key_one')->exists())->toBeTrue();
    expect(Setting::where('key', 'key_two')->exists())->toBeTrue();
    expect(Setting::where('key', 'key_three')->exists())->toBeTrue();
});

test('batch set with array config applies type and group', function () {
    $action = new BatchSetSettingAction(new SetSettingAction);
    $settings = [
        'encrypted_key' => [
            'value' => 'secret',
            'type' => 'encrypted',
            'group' => 'system',
            'description' => 'A secret',
        ],
    ];

    $results = $action->execute($settings);
    $setting = $results->first();

    expect($setting->type)->toBe('encrypted');
    expect($setting->group)->toBe('system');
    expect($setting->description)->toBe('A secret');
});

test('batch set is transactional', function () {
    $action = new BatchSetSettingAction(new SetSettingAction);

    $results = $action->execute(['key_a' => 'val_a', 'key_b' => 'val_b']);

    expect($results)->toHaveCount(2);
});
