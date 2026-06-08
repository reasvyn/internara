<?php

declare(strict_types=1);

use App\Setup\Data\SetupData;

test('setup data can be created', function () {
    $data = new SetupData(name: 'Test School', institutionalCode: '12345', email: 's@test.com');

    expect($data->name)->toBe('Test School');
    expect($data->institutionalCode)->toBe('12345');
    expect($data->email)->toBe('s@test.com');
    expect($data->address)->toBe('');
});

test('setup data is immutable', function () {
    $data = new SetupData(name: 'N', institutionalCode: 'C', email: 'e@t.com');

    $reflection = new ReflectionClass($data);
    foreach ($reflection->getProperties() as $prop) {
        expect($prop->isReadOnly())->toBeTrue();
    }
});