<?php

declare(strict_types=1);

namespace Modules\User\Tests\Unit\Models;

use Modules\User\Models\User;

test('it generates correct initials', function () {
    $user = new User(['name' => 'John Doe']);
    expect($user->initials())->toBe('JD');

    $user = new User(['name' => 'Reas Vyn Official']);
    expect($user->initials())->toBe('RV');

    $user = new User(['name' => 'Single']);
    expect($user->initials())->toBe('S');
});

test('it uses uuid if configured', function () {
    config(['user.type_id' => 'uuid']);
    $user = new User();

    // We access the protected method via reflection if necessary,
    // but here we can just check if it's called during boot or check the property
    $reflection = new \ReflectionClass($user);
    $method = $reflection->getMethod('usesUuid');
    $method->setAccessible(true);

    expect($method->invoke($user))->toBeTrue();
});
