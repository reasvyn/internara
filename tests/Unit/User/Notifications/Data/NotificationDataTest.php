<?php

declare(strict_types=1);

use App\User\Notifications\Data\NotificationData;

test('notification data can be created with required fields', function () {
    $data = new NotificationData(userId: 'user-1', type: 'info', title: 'Hello');

    expect($data->userId)->toBe('user-1');
    expect($data->type)->toBe('info');
    expect($data->title)->toBe('Hello');
    expect($data->message)->toBeNull();
});

test('notification data can be created with all fields', function () {
    $data = new NotificationData(
        userId: 'user-1',
        type: 'warning',
        title: 'Warning',
        message: 'Something happened',
        data: ['key' => 'value'],
        link: '/settings',
    );

    expect($data->message)->toBe('Something happened');
    expect($data->data)->toBe(['key' => 'value']);
    expect($data->link)->toBe('/settings');
});

test('notification data is immutable', function () {
    $data = new NotificationData(userId: '1', type: 't', title: 'T');

    $reflection = new ReflectionClass($data);
    foreach ($reflection->getProperties() as $prop) {
        expect($prop->isReadOnly())->toBeTrue();
    }
});