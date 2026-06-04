<?php

declare(strict_types=1);

use App\Domain\Core\Contracts\StatusEnum;

test('StatusEnum extends LabelEnum', function () {
    $ref = new ReflectionClass(StatusEnum::class);
    $interfaces = $ref->getInterfaceNames();
    expect($interfaces)->toContain('App\Domain\Core\Contracts\LabelEnum');
});

test('StatusEnum contract requires all state machine methods', function () {
    $ref = new ReflectionClass(StatusEnum::class);
    expect($ref->hasMethod('canTransitionTo'))->toBeTrue();
    expect($ref->getMethod('canTransitionTo')->getReturnType()->getName())->toBe('bool');

    expect($ref->hasMethod('isTerminal'))->toBeTrue();
    expect($ref->getMethod('isTerminal')->getReturnType()->getName())->toBe('bool');

    expect($ref->hasMethod('validTransitions'))->toBeTrue();
    expect($ref->getMethod('validTransitions')->getReturnType()->getName())->toBe('array');
});
