<?php

declare(strict_types=1);

namespace Tests\Feature\Setup\SetupWizard\Events;

use App\Setup\SetupWizard\Events\SetupFinalized;
use Illuminate\Support\Facades\Event;

test(
    'setup finalized event can be dispatched and contains department id and installed at',
    function () {
        Event::fake();

        $installedAt = new \DateTimeImmutable('2026-06-05 12:00:00');
        Event::dispatch(
            new SetupFinalized(departmentId: 'uuid-dept-456', installedAt: $installedAt),
        );

        Event::assertDispatched(SetupFinalized::class, function (SetupFinalized $event) {
            return $event->departmentId === 'uuid-dept-456' &&
                $event->installedAt instanceof \DateTimeImmutable;
        });
    },
);
