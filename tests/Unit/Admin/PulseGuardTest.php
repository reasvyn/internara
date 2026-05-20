<?php

declare(strict_types=1);

use App\Domain\Admin\Services\PulseGuard;

describe('PulseGuard', function () {
    it('denies null user', function () {
        expect(PulseGuard::viewPulse(null))->toBeFalse();
    });

    it('is a final class', function () {
        $ref = new ReflectionClass(PulseGuard::class);

        expect($ref->isFinal())->toBeTrue();
    });
});
