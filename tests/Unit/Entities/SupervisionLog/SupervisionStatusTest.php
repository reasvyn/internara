<?php

declare(strict_types=1);

use App\Entities\SupervisionLog\SupervisionStatus;
use App\Enums\Mentor\SupervisionLogStatus;

it('detects completed supervision', function () {
    $entity = new SupervisionStatus(SupervisionLogStatus::COMPLETED);

    expect($entity->isCompleted())->toBeTrue();
});

it('detects not completed supervision', function () {
    $entity = new SupervisionStatus(SupervisionLogStatus::PENDING);

    expect($entity->isCompleted())->toBeFalse();
});

it('detects active supervision', function () {
    $entity = new SupervisionStatus(SupervisionLogStatus::IN_PROGRESS);

    expect($entity->isActive())->toBeTrue();
});

it('detects not active supervision', function () {
    $entity = new SupervisionStatus(SupervisionLogStatus::COMPLETED);

    expect($entity->isActive())->toBeFalse();
});

it('returns defaults when status is null', function () {
    $entity = new SupervisionStatus(null);

    expect($entity->isCompleted())->toBeFalse()
        ->and($entity->isActive())->toBeFalse();
});
