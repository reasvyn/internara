<?php

declare(strict_types=1);

use App\Setup\SetupWizard\Events\SetupFinalized;

test('setup finalized event name and payload', function () {
    $installedAt = new DateTimeImmutable('2026-07-01 12:00:00');
    $event = new SetupFinalized(departmentId: 'dept-1', installedAt: $installedAt);

    expect($event->departmentId)->toBe('dept-1');
    expect($event->installedAt)->toBe($installedAt);
    expect($event->eventName())->toBe('setup.finalized');
    expect($event->toPayload())->toHaveKey('departmentId');
});

test('setup finalized event allows null department id', function () {
    $installedAt = new DateTimeImmutable('2026-07-01 12:00:00');
    $event = new SetupFinalized(departmentId: null, installedAt: $installedAt);

    expect($event->departmentId)->toBeNull();
    expect($event->toPayload())->toHaveKey('departmentId');
    expect($event->toPayload()['departmentId'])->toBeNull();
});
