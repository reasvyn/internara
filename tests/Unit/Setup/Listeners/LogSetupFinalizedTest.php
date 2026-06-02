<?php

declare(strict_types=1);

use App\Domain\Setup\Events\SetupFinalized;
use App\Domain\Setup\Listeners\LogSetupFinalized;

describe('LogSetupFinalized', function () {
    it('logs without throwing', function () {
        $event = new SetupFinalized(schoolId: 'uuid-1', installedAt: new DateTimeImmutable);
        $listener = new LogSetupFinalized;

        $listener->handle($event);

        expect(true)->toBeTrue();
    });

    it('handles null school id gracefully', function () {
        $event = new SetupFinalized(schoolId: null, installedAt: new DateTimeImmutable);
        $listener = new LogSetupFinalized;

        $listener->handle($event);

        expect(true)->toBeTrue();
    });
});
