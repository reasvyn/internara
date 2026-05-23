<?php

declare(strict_types=1);

use App\Domain\Core\Support\Integrity;

describe('Integrity', function () {
    it('is a final class', function () {
        $ref = new ReflectionClass(Integrity::class);

        expect($ref->isFinal())->toBeTrue();
    });

    it('has verify method', function () {
        expect(method_exists(Integrity::class, 'verify'))->toBeTrue();
    });

    it('verify runs without exception in unit tests', function () {
        Integrity::verify();

        expect(true)->toBeTrue();
    });

    it('has no public methods other than verify', function () {
        $ref = new ReflectionClass(Integrity::class);
        $methods = $ref->getMethods(ReflectionMethod::IS_PUBLIC);
        $methodNames = array_map(fn ($m) => $m->getName(), $methods);

        expect($methodNames)->toBe(['verify']);
    });

    it('verify early-returns in test environment', function () {
        $result = Integrity::verify();

        expect($result)->toBeNull();
    });
});
