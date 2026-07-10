<?php

declare(strict_types=1);

use App\Academics\AcademicYear\Entities\AcademicYearState;

test('is active returns true when model has active flag', function () {
    $entity = new AcademicYearState(isActive: true);

    expect($entity->isActive())->toBeTrue();
});

test('is active returns false when model has inactive flag', function () {
    $entity = new AcademicYearState(isActive: false);

    expect($entity->isActive())->toBeFalse();
});

test('can be activated returns true when inactive', function () {
    $entity = new AcademicYearState(isActive: false);

    expect($entity->canBeActivated())->toBeTrue();
});

test('can be activated returns false when already active', function () {
    $entity = new AcademicYearState(isActive: true);

    expect($entity->canBeActivated())->toBeFalse();
});

test('can be deleted returns true when inactive and no related records', function () {
    $entity = new AcademicYearState(isActive: false, hasRelatedRecords: false);

    expect($entity->canBeDeleted())->toBeTrue();
});

test('can be deleted returns false when active', function () {
    $entity = new AcademicYearState(isActive: true, hasRelatedRecords: false);

    expect($entity->canBeDeleted())->toBeFalse();
});

test('can be deleted returns false when has related records', function () {
    $entity = new AcademicYearState(isActive: false, hasRelatedRecords: true);

    expect($entity->canBeDeleted())->toBeFalse();
});

test('has related records returns true when records exist', function () {
    $entity = new AcademicYearState(isActive: false, hasRelatedRecords: true);

    expect($entity->hasRelatedRecords())->toBeTrue();
});

test('has related records returns false when no records', function () {
    $entity = new AcademicYearState(isActive: false, hasRelatedRecords: false);

    expect($entity->hasRelatedRecords())->toBeFalse();
});
