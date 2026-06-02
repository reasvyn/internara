<?php

declare(strict_types=1);

use App\Domain\Setup\Events\SetupFinalized;

describe('SetupFinalized', function () {
    it('is readonly event', function () {
        $ref = new ReflectionClass(SetupFinalized::class);

        expect($ref->isReadOnly())->toBeTrue();
    });

    it('carries school id and installed at', function () {
        $now = new DateTimeImmutable;
        $event = new SetupFinalized(schoolId: 'uuid-123', installedAt: $now);

        expect($event->schoolId)->toBe('uuid-123')
            ->and($event->installedAt)->toBe($now);
    });

    it('allows null school id', function () {
        $event = new SetupFinalized(schoolId: null, installedAt: new DateTimeImmutable);

        expect($event->schoolId)->toBeNull();
    });
});
