<?php

declare(strict_types=1);

use App\Journals\AbsenceRequest\Entities\AbsenceRequestState;
use App\Journals\AbsenceRequest\Enums\AbsenceRequestStatus;

test('absence request status entity detects pending', function () {
    $entity = new AbsenceRequestState(AbsenceRequestStatus::PENDING);

    expect($entity->isPending())->toBeTrue();
    expect($entity->isProcessed())->toBeFalse();
});

test('absence request status entity detects processed', function () {
    $approved = new AbsenceRequestState(AbsenceRequestStatus::APPROVED);
    $rejected = new AbsenceRequestState(AbsenceRequestStatus::REJECTED);

    expect($approved->isPending())->toBeFalse();
    expect($approved->isProcessed())->toBeTrue();
    expect($rejected->isProcessed())->toBeTrue();
});

test('absence request status entity with null status', function () {
    $entity = new AbsenceRequestState(null);

    expect($entity->isPending())->toBeFalse();
    expect($entity->isProcessed())->toBeFalse();
});
