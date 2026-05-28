<?php

declare(strict_types=1);

use App\Domain\Setup\Events\SetupFinalized;

describe('SetupFinalized event', function () {
    it('carries school id and installation timestamp', function () {
        $event = new SetupFinalized(
            schoolId: 'some-uuid',
            installedAt: new DateTimeImmutable('2026-01-01 12:00:00'),
        );

        expect($event->schoolId)->toBe('some-uuid')
            ->and($event->installedAt)->toBeInstanceOf(DateTimeImmutable::class);
    });

    it('allows null school id', function () {
        $event = new SetupFinalized(
            schoolId: null,
            installedAt: new DateTimeImmutable('2026-01-01 12:00:00'),
        );

        expect($event->schoolId)->toBeNull();
    });

    it('is final readonly', function () {
        $ref = new ReflectionClass(SetupFinalized::class);

        expect($ref->isFinal())->toBeTrue()
            ->and($ref->isReadOnly())->toBeTrue();
    });
});
