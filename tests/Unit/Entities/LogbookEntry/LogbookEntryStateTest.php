<?php

declare(strict_types=1);

use App\Entities\LogbookEntry\LogbookEntryState;
use App\Enums\Logbook\LogbookEntryStatus;

it('detects verified logbook entry', function () {
    $entity = new LogbookEntryState(LogbookEntryStatus::VERIFIED);

    expect($entity->isVerified())->toBeTrue();
});

it('detects not verified logbook entry', function () {
    $entity = new LogbookEntryState(LogbookEntryStatus::DRAFT);

    expect($entity->isVerified())->toBeFalse();
});

it('can be edited when draft', function () {
    $entity = new LogbookEntryState(LogbookEntryStatus::DRAFT);

    expect($entity->canBeEdited())->toBeTrue();
});

it('can be edited when revision required', function () {
    $entity = new LogbookEntryState(LogbookEntryStatus::REVISION_REQUIRED);

    expect($entity->canBeEdited())->toBeTrue();
});

it('cannot be edited when submitted', function () {
    $entity = new LogbookEntryState(LogbookEntryStatus::SUBMITTED);

    expect($entity->canBeEdited())->toBeFalse();
});

it('cannot be edited when verified', function () {
    $entity = new LogbookEntryState(LogbookEntryStatus::VERIFIED);

    expect($entity->canBeEdited())->toBeFalse();
});
