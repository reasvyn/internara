<?php

declare(strict_types=1);

use App\Core\Contracts\SendsNotifications;

test('SendsNotifications interface exists', function () {
    expect(interface_exists(SendsNotifications::class))->toBeTrue();
});

test('SendsNotifications requires execute method', function () {
    $ref = new ReflectionMethod(SendsNotifications::class, 'execute');

    expect($ref->isPublic())->toBeTrue();
    expect((string) $ref->getReturnType())->toBe('mixed');
});

test('SendsNotifications execute accepts expected parameters', function () {
    $ref = new ReflectionMethod(SendsNotifications::class, 'execute');
    $params = $ref->getParameters();

    expect($params)->toHaveCount(6);
    expect($params[0]->getName())->toBe('userId');
    expect($params[1]->getName())->toBe('type');
    expect($params[2]->getName())->toBe('title');
    expect($params[3]->getName())->toBe('message');
    expect($params[4]->getName())->toBe('data');
    expect($params[5]->getName())->toBe('link');
});
