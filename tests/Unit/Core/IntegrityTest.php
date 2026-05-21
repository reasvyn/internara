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
});
