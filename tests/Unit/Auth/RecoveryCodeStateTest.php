<?php

declare(strict_types=1);

use App\Domain\Auth\Entities\RecoveryCodeState;
use Carbon\Carbon;

describe('RecoveryCodeState', function () {
    it('is valid when not used and not expired', function () {
        $state = new RecoveryCodeState(
            usedAt: null,
            expiresAt: Carbon::now()->addHour(),
        );

        expect($state->isValid())->toBeTrue();
    });

    it('is invalid when used', function () {
        $state = new RecoveryCodeState(
            usedAt: Carbon::now(),
            expiresAt: Carbon::now()->addHour(),
        );

        expect($state->isValid())->toBeFalse();
    });

    it('is invalid when expired', function () {
        $state = new RecoveryCodeState(
            usedAt: null,
            expiresAt: Carbon::now()->subHour(),
        );

        expect($state->isValid())->toBeFalse();
    });

    it('is valid when expires_at is null (no expiry)', function () {
        $state = new RecoveryCodeState(
            usedAt: null,
            expiresAt: null,
        );

        expect($state->isValid())->toBeTrue();
    });

    it('is final readonly', function () {
        $ref = new ReflectionClass(RecoveryCodeState::class);

        expect($ref->isFinal())->toBeTrue()
            ->and($ref->isReadOnly())->toBeTrue();
    });
});
