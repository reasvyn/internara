<?php

declare(strict_types=1);

use App\Journals\AbsenceRequest\Enums\AbsenceRequestStatus;
use App\Journals\AbsenceRequest\Entities\AbsenceRequestStatus as AbsenceRequestStatusEntity;

test('absence request status entity detects pending', function () {
    $entity = new AbsenceRequestStatusEntity(AbsenceRequestStatus::PENDING);

    expect($entity->isPending())->toBeTrue();
    expect($entity->isProcessed())->toBeFalse();
});

test('absence request status entity detects processed', function () {
    $approved = new AbsenceRequestStatusEntity(AbsenceRequestStatus::APPROVED);
    $rejected = new AbsenceRequestStatusEntity(AbsenceRequestStatus::REJECTED);

    expect($approved->isPending())->toBeFalse();
    expect($approved->isProcessed())->toBeTrue();
    expect($rejected->isProcessed())->toBeTrue();
});

test('absence request status entity with null status', function () {
    $entity = new AbsenceRequestStatusEntity(null);

    expect($entity->isPending())->toBeFalse();
    expect($entity->isProcessed())->toBeFalse();
});
