<?php

declare(strict_types=1);

use App\Core\Contracts\LabelEnum;

test('LabelEnum interface requires label method', function () {
    $ref = new ReflectionClass(LabelEnum::class);
    expect($ref->isInterface())->toBeTrue();
    expect($ref->hasMethod('label'))->toBeTrue();
    expect($ref->getMethod('label')->getReturnType()->getName())->toBe('string');
});
