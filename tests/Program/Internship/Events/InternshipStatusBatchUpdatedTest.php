<?php

declare(strict_types=1);

use App\Program\Internship\Events\InternshipStatusBatchUpdated;

test('internship status batch updated event name and payload', function () {
    $event = new InternshipStatusBatchUpdated(
        count: 10,
        newStatus: 'active',
        previousStatus: 'pending',
    );

    expect($event->count)->toBe(10);
    expect($event->newStatus)->toBe('active');
    expect($event->previousStatus)->toBe('pending');
    expect($event->eventName())->toBe('internship.status_batch_updated');
    expect($event->toPayload())->toHaveKey('count');
    expect($event->toPayload())->toHaveKey('newStatus');
    expect($event->toPayload())->toHaveKey('previousStatus');
});

test('internship status batch updated event allows null previous status', function () {
    $event = new InternshipStatusBatchUpdated(count: 5, newStatus: 'completed');

    expect($event->previousStatus)->toBeNull();
    expect($event->toPayload())->toHaveKey('previousStatus');
    expect($event->toPayload()['previousStatus'])->toBeNull();
});
