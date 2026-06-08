<?php

declare(strict_types=1);

use App\Setup\Data\AdminData;

test('admin data can be created', function () {
    $data = new AdminData(email: 'admin@test.com', password: 'secret123');

    expect($data->email)->toBe('admin@test.com');
    expect($data->password)->toBe('secret123');
});

test('admin data is immutable', function () {
    $data = new AdminData(email: 'a@b.com', password: 'p');

    $reflection = new ReflectionClass($data);
    foreach ($reflection->getProperties() as $prop) {
        expect($prop->isReadOnly())->toBeTrue();
    }
});

test('admin data from array', function () {
    $data = AdminData::from(['email' => 'admin@test.com', 'password' => 'secret']);

    expect($data->email)->toBe('admin@test.com');
    expect($data->password)->toBe('secret');
});