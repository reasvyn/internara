<?php

declare(strict_types=1);

use App\Domain\Core\Contracts\ColorableEnum;
use App\Domain\Core\Contracts\DomainEvent;
use App\Domain\Core\Contracts\LabelEnum;
use App\Domain\Core\Contracts\StatusEnum;

describe('Core contracts', function () {
    it('LabelEnum requires label method', function () {
        $ref = new ReflectionMethod(LabelEnum::class, 'label');

        expect($ref->getReturnType())->not->toBeNull();
    });

    it('StatusEnum extends LabelEnum', function () {
        $ref = new ReflectionClass(StatusEnum::class);

        expect($ref->getInterfaceNames())->toContain(LabelEnum::class);
    });

    it('StatusEnum requires isTerminal with bool return', function () {
        $ref = new ReflectionMethod(StatusEnum::class, 'isTerminal');

        expect($ref->getReturnType()?->getName())->toBe('bool');
    });

    it('StatusEnum requires canTransitionTo', function () {
        expect(method_exists(StatusEnum::class, 'canTransitionTo'))->toBeTrue();
    });

    it('StatusEnum requires validTransitions', function () {
        expect(method_exists(StatusEnum::class, 'validTransitions'))->toBeTrue();
    });

    it('ColorableEnum requires color method', function () {
        $ref = new ReflectionMethod(ColorableEnum::class, 'color');

        expect($ref->getReturnType()?->getName())->toBe('string');
    });

    it('DomainEvent requires occurredAt returning DateTimeImmutable', function () {
        $ref = new ReflectionMethod(DomainEvent::class, 'occurredAt');

        expect($ref->getReturnType()?->getName())->toBe('DateTimeImmutable');
    });
});
