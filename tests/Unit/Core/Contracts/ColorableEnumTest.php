<?php

declare(strict_types=1);

use App\Core\Contracts\ColorableEnum;

test('ColorableEnum interface requires color method', function () {
    $ref = new ReflectionClass(ColorableEnum::class);
    expect($ref->isInterface())->toBeTrue();
    expect($ref->hasMethod('color'))->toBeTrue();
    expect($ref->getMethod('color')->getReturnType()->getName())->toBe('string');
});
