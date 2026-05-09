<?php

declare(strict_types=1);

use App\Entities\Logbook\LogbookState;
use App\Enums\Logbook\LogbookStatus;

it('detects verified logbook entry', function () {
    $entity = new LogbookState(LogbookStatus::VERIFIED);

    expect($entity->isVerified())->toBeTrue();
});

it('detects not verified logbook entry', function () {
    $entity = new LogbookState(LogbookStatus::DRAFT);

    expect($entity->isVerified())->toBeFalse();
});

it('can be edited when draft', function () {
    $entity = new LogbookState(LogbookStatus::DRAFT);

    expect($entity->canBeEdited())->toBeTrue();
});

it('can be edited when revision required', function () {
    $entity = new LogbookState(LogbookStatus::REVISION_REQUIRED);

    expect($entity->canBeEdited())->toBeTrue();
});

it('cannot be edited when submitted', function () {
    $entity = new LogbookState(LogbookStatus::SUBMITTED);

    expect($entity->canBeEdited())->toBeFalse();
});

it('cannot be edited when verified', function () {
    $entity = new LogbookState(LogbookStatus::VERIFIED);

    expect($entity->canBeEdited())->toBeFalse();
});
