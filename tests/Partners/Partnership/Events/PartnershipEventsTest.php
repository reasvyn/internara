<?php

declare(strict_types=1);

use App\Partners\Partnership\Events\PartnershipCreated;
use App\Partners\Partnership\Events\PartnershipDeleted;
use App\Partners\Partnership\Events\PartnershipRenewed;
use App\Partners\Partnership\Events\PartnershipTerminated;
use App\Partners\Partnership\Events\PartnershipUpdated;
use App\Partners\Partnership\Models\Partnership;

function makePartnership(string $id): Partnership
{
    $model = new class extends Partnership {};
    $model->forceFill(['id' => $id]);

    return $model;
}

test('partnership created event name and payload', function () {
    $event = new PartnershipCreated(makePartnership('p-1'));

    expect($event->partnership->id)->toBe('p-1');
    expect($event->eventName())->toBe('partnership.created');
    expect($event->toPayload())->toHaveKey('partnership_id');
});

test('partnership updated event name and payload', function () {
    $event = new PartnershipUpdated(makePartnership('p-2'));

    expect($event->partnership->id)->toBe('p-2');
    expect($event->eventName())->toBe('partnership.updated');
    expect($event->toPayload())->toHaveKey('partnership_id');
});

test('partnership deleted event name and payload', function () {
    $event = new PartnershipDeleted(makePartnership('p-3'));

    expect($event->partnership->id)->toBe('p-3');
    expect($event->eventName())->toBe('partnership.deleted');
    expect($event->toPayload())->toHaveKey('partnership_id');
});

test('partnership terminated event name and payload', function () {
    $event = new PartnershipTerminated(makePartnership('p-4'));

    expect($event->partnership->id)->toBe('p-4');
    expect($event->eventName())->toBe('partnership.terminated');
    expect($event->toPayload())->toHaveKey('partnership_id');
});

test('partnership renewed event carries new and old partnership', function () {
    $event = new PartnershipRenewed(
        newPartnership: makePartnership('p-new'),
        oldPartnership: makePartnership('p-old'),
    );

    expect($event->newPartnership->id)->toBe('p-new');
    expect($event->oldPartnership->id)->toBe('p-old');
    expect($event->eventName())->toBe('partnership.renewed');
    expect($event->toPayload())->toHaveKey('newPartnership_id');
    expect($event->toPayload())->toHaveKey('oldPartnership_id');
});
