<?php

declare(strict_types=1);

use App\Auth\Login\Data\LoginData;

test('login data can be created', function () {
    $data = new LoginData(identifier: 'user@test.com', password: 'secret', remember: true);

    expect($data->identifier)->toBe('user@test.com');
    expect($data->password)->toBe('secret');
    expect($data->remember)->toBeTrue();
});

test('login data defaults to not remember', function () {
    $data = new LoginData(identifier: 'admin', password: 'pass');

    expect($data->remember)->toBeFalse();
});

test('login data is immutable', function () {
    $data = new LoginData(identifier: 'u', password: 'p');

    $reflection = new ReflectionClass($data);
    foreach ($reflection->getProperties() as $prop) {
        expect($prop->isReadOnly())->toBeTrue();
    }
});