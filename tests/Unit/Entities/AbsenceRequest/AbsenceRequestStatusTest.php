<?php

declare(strict_types=1);

use App\Entities\AbsenceRequest\AbsenceRequestStatus;
use App\Enums\Attendance\AbsenceRequestStatus as AbsenceRequestStatusEnum;

it('detects pending absence request', function () {
    $entity = new AbsenceRequestStatus(AbsenceRequestStatusEnum::PENDING);

    expect($entity->isPending())->toBeTrue();
});

it('detects not pending', function () {
    $entity = new AbsenceRequestStatus(AbsenceRequestStatusEnum::APPROVED);

    expect($entity->isPending())->toBeFalse();
});

it('detects processed absence request', function () {
    $entity = new AbsenceRequestStatus(AbsenceRequestStatusEnum::APPROVED);

    expect($entity->isProcessed())->toBeTrue();
});

it('detects not processed', function () {
    $entity = new AbsenceRequestStatus(AbsenceRequestStatusEnum::PENDING);

    expect($entity->isProcessed())->toBeFalse();
});
