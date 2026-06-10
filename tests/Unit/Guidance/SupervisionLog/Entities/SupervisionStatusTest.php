<?php

declare(strict_types=1);

use App\Guidance\SupervisionLog\Enums\SupervisionLogStatus;
use App\Guidance\SupervisionLog\Entities\SupervisionStatus;

test('supervision status can be created with terminal status', function () {
    $entity = new SupervisionStatus(SupervisionLogStatus::COMPLETED);

    expect($entity->isCompleted())->toBeTrue();
    expect($entity->isActive())->toBeFalse();
});

test('supervision status with active status', function () {
    $entity = new SupervisionStatus(SupervisionLogStatus::PENDING);

    expect($entity->isCompleted())->toBeFalse();
    expect($entity->isActive())->toBeTrue();
});

test('supervision status with null status', function () {
    $entity = new SupervisionStatus(null);

    expect($entity->isCompleted())->toBeFalse();
    expect($entity->isActive())->toBeFalse();
});

test('supervision status is immutable', function () {
    $entity = new SupervisionStatus(SupervisionLogStatus::VERIFIED);

    $reflection = new ReflectionClass($entity);
    foreach ($reflection->getProperties() as $prop) {
        expect($prop->isReadOnly())->toBeTrue();
    }
});
