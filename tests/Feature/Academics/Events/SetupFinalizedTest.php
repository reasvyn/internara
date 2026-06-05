<?php

declare(strict_types=1);

namespace Tests\Feature\Academics\Events;

use App\Academics\Events\SetupFinalized;
use Illuminate\Support\Facades\Event;

test('setup finalized event can be dispatched and contains school id and installed at', function () {
    Event::fake();

    $installedAt = new \DateTimeImmutable('2026-06-05 12:00:00');
    Event::dispatch(new SetupFinalized(
        schoolId: 'uuid-school-123',
        installedAt: $installedAt,
    ));

    Event::assertDispatched(SetupFinalized::class, function (SetupFinalized $event) {
        return $event->schoolId === 'uuid-school-123'
            && $event->installedAt instanceof \DateTimeImmutable;
    });
});
