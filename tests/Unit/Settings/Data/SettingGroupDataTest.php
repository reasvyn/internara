<?php

declare(strict_types=1);

use App\Settings\Data\SettingGroupData;

test('setting group data can be created', function () {
    $data = new SettingGroupData(name: 'general', count: 5);

    expect($data->name)->toBe('general');
    expect($data->count)->toBe(5);
});

test('setting group data defaults count to 0', function () {
    $data = new SettingGroupData(name: 'branding');

    expect($data->count)->toBe(0);
});

test('setting group data from array', function () {
    $data = SettingGroupData::from(['name' => 'mail', 'count' => 3]);

    expect($data->name)->toBe('mail');
    expect($data->count)->toBe(3);
});