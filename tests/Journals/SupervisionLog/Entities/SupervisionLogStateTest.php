<?php

declare(strict_types=1);

use App\Journals\SupervisionLog\Entities\SupervisionLogState;
use App\Journals\SupervisionLog\Enums\SupervisionLogStatus;

test('supervision log state can be edited only when draft', function () {
    $draft = new SupervisionLogState(SupervisionLogStatus::DRAFT, null, null);
    expect($draft->canBeEdited())->toBeTrue();

    $submitted = new SupervisionLogState(SupervisionLogStatus::SUBMITTED, null, null);
    expect($submitted->canBeEdited())->toBeFalse();

    $reviewed = new SupervisionLogState(SupervisionLogStatus::REVIEWED, null, null);
    expect($reviewed->canBeEdited())->toBeFalse();

    $acknowledged = new SupervisionLogState(SupervisionLogStatus::ACKNOWLEDGED, null, null);
    expect($acknowledged->canBeEdited())->toBeFalse();
});

test('supervision log state can be submitted only when draft', function () {
    $draft = new SupervisionLogState(SupervisionLogStatus::DRAFT, null, null);
    expect($draft->canBeSubmitted())->toBeTrue();

    $submitted = new SupervisionLogState(SupervisionLogStatus::SUBMITTED, null, null);
    expect($submitted->canBeSubmitted())->toBeFalse();
});

test('supervision log state needs acknowledgment only when reviewed', function () {
    $reviewed = new SupervisionLogState(SupervisionLogStatus::REVIEWED, null, null);
    expect($reviewed->needsAcknowledgment())->toBeTrue();

    $draft = new SupervisionLogState(SupervisionLogStatus::DRAFT, null, null);
    expect($draft->needsAcknowledgment())->toBeFalse();

    $submitted = new SupervisionLogState(SupervisionLogStatus::SUBMITTED, null, null);
    expect($submitted->needsAcknowledgment())->toBeFalse();

    $acknowledged = new SupervisionLogState(SupervisionLogStatus::ACKNOWLEDGED, null, null);
    expect($acknowledged->needsAcknowledgment())->toBeFalse();
});
