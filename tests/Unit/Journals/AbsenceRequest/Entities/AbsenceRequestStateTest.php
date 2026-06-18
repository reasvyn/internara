<?php

declare(strict_types=1);

use App\Journals\AbsenceRequest\Entities\AbsenceRequestState;
use App\Journals\AbsenceRequest\Enums\AbsenceRequestStatus;

test('absence request state detects pending', function () {
    $pending = new AbsenceRequestState(AbsenceRequestStatus::PENDING);
    expect($pending->isPending())->toBeTrue();

    $approved = new AbsenceRequestState(AbsenceRequestStatus::APPROVED);
    expect($approved->isPending())->toBeFalse();

    $rejected = new AbsenceRequestState(AbsenceRequestStatus::REJECTED);
    expect($rejected->isPending())->toBeFalse();
});

test('absence request state detects processed status', function () {
    $approved = new AbsenceRequestState(AbsenceRequestStatus::APPROVED);
    expect($approved->isProcessed())->toBeTrue();

    $rejected = new AbsenceRequestState(AbsenceRequestStatus::REJECTED);
    expect($rejected->isProcessed())->toBeTrue();

    $pending = new AbsenceRequestState(AbsenceRequestStatus::PENDING);
    expect($pending->isProcessed())->toBeFalse();
});

test('absence request state with null status is not pending and not processed', function () {
    $state = new AbsenceRequestState(null);
    expect($state->isPending())->toBeFalse();
    expect($state->isProcessed())->toBeFalse();
});
