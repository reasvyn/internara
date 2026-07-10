<?php

declare(strict_types=1);

use App\Academics\AcademicYear\Entities\AcademicYearState;

test('academic year state detects active', function () {
    $active = new AcademicYearState(isActive: true);
    expect($active->isActive())->toBeTrue();
    expect($active->canBeActivated())->toBeFalse();

    $inactive = new AcademicYearState(isActive: false);
    expect($inactive->isActive())->toBeFalse();
    expect($inactive->canBeActivated())->toBeTrue();
});

test('academic year state can be deleted only when inactive and no related records', function () {
    $activeNoRecords = new AcademicYearState(isActive: true, hasRelatedRecords: false);
    expect($activeNoRecords->canBeDeleted())->toBeFalse();

    $activeWithRecords = new AcademicYearState(isActive: true, hasRelatedRecords: true);
    expect($activeWithRecords->canBeDeleted())->toBeFalse();

    $inactiveWithRecords = new AcademicYearState(isActive: false, hasRelatedRecords: true);
    expect($inactiveWithRecords->canBeDeleted())->toBeFalse();

    $inactiveNoRecords = new AcademicYearState(isActive: false, hasRelatedRecords: false);
    expect($inactiveNoRecords->canBeDeleted())->toBeTrue();
});

test('academic year state has related records', function () {
    $withRecords = new AcademicYearState(isActive: false, hasRelatedRecords: true);
    expect($withRecords->hasRelatedRecords())->toBeTrue();

    $withoutRecords = new AcademicYearState(isActive: false, hasRelatedRecords: false);
    expect($withoutRecords->hasRelatedRecords())->toBeFalse();
});
