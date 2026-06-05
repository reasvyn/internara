<?php

declare(strict_types=1);

use App\Core\Contracts\SettingsStore;

test('SettingsStore interface exists', function () {
    expect(interface_exists(SettingsStore::class))->toBeTrue();
});

test('SettingsStore requires get method', function () {
    $ref = new ReflectionMethod(SettingsStore::class, 'get');

    expect($ref->isPublic())->toBeTrue();
    expect((string) $ref->getReturnType())->toBe('mixed');
});

test('SettingsStore get accepts key with default', function () {
    $ref = new ReflectionMethod(SettingsStore::class, 'get');
    $params = $ref->getParameters();

    expect($params)->toHaveCount(2);
    expect($params[0]->getName())->toBe('key');
    expect((string) $params[0]->getType())->toBe('string');
    expect($params[1]->getName())->toBe('default');
    expect($params[1]->isDefaultValueAvailable())->toBeTrue();
    expect($params[1]->getDefaultValue())->toBeNull();
});
