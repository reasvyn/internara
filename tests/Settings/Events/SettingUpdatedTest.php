<?php

declare(strict_types=1);

use App\Settings\Data\SettingData;
use App\Settings\Events\SettingUpdated;

test('setting updated event name for created', function () {
    $setting = new SettingData(key: 'app.name', value: 'Internara');
    $event = new SettingUpdated($setting, wasRecentlyCreated: true);

    expect($event->eventName())->toBe('setting.created');
    expect($event->toPayload())->toHaveKey('setting');
    expect($event->toPayload()['setting']['key'])->toBe('app.name');
});

test('setting updated event name for updated', function () {
    $setting = new SettingData(key: 'app.name', value: 'Internara 2');
    $event = new SettingUpdated($setting, wasRecentlyCreated: false);

    expect($event->eventName())->toBe('setting.updated');
    expect($event->toPayload())->toHaveKey('wasRecentlyCreated');
    expect($event->toPayload()['wasRecentlyCreated'])->toBeFalse();
});

test('setting data serializes in payload', function () {
    $setting = new SettingData(
        key: 'site.title',
        value: 'My School',
        type: 'string',
        group: 'general',
    );
    $event = new SettingUpdated($setting, wasRecentlyCreated: false);

    $payload = $event->toPayload();

    expect($payload['setting'])->toHaveKeys(['key', 'value', 'type', 'group']);
    expect($payload['setting']['key'])->toBe('site.title');
    expect($payload['setting']['value'])->toBe('My School');
});
