<?php

declare(strict_types=1);

use App\Document\Handbook\Events\HandbookCreated;
use App\Document\Handbook\Events\HandbookDeleted;
use App\Document\Handbook\Events\HandbookUpdated;
use App\Document\Models\Document;

function makeHandbook(string $id): Document
{
    $model = new class extends Document {};
    $model->forceFill(['id' => $id]);

    return $model;
}

test('handbook created event name and payload', function () {
    $event = new HandbookCreated(makeHandbook('h-1'));

    expect($event->handbook->id)->toBe('h-1');
    expect($event->eventName())->toBe('handbook.created');
    expect($event->toPayload())->toHaveKey('handbook_id');
});

test('handbook updated event name and payload', function () {
    $event = new HandbookUpdated(makeHandbook('h-2'));

    expect($event->handbook->id)->toBe('h-2');
    expect($event->eventName())->toBe('handbook.updated');
    expect($event->toPayload())->toHaveKey('handbook_id');
});

test('handbook deleted event name and payload', function () {
    $event = new HandbookDeleted(makeHandbook('h-3'));

    expect($event->handbook->id)->toBe('h-3');
    expect($event->eventName())->toBe('handbook.deleted');
    expect($event->toPayload())->toHaveKey('handbook_id');
});
