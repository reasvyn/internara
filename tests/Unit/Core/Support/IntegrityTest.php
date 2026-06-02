<?php

declare(strict_types=1);

use App\Domain\Core\Support\Integrity;

describe('Integrity', function () {
    it('verifies without throwing in testing environment', function () {
        Integrity::verify();

        expect(true)->toBeTrue();
    });

    it('is a final class', function () {
        $ref = new ReflectionClass(Integrity::class);

        expect($ref->isFinal())->toBeTrue();
    });
});
